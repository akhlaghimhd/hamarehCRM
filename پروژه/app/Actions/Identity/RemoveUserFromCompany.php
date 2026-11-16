<?php

namespace App\Actions\Identity;

use App\Models\AuditEvent;
use App\Models\Role;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class RemoveUserFromCompany
{
    public function handle(int $companyId, int $userId, User $actor): void
    {
        if (! $actor->is_active
            || (int) $actor->currentCompanyId() !== $companyId
            || ! $actor->hasPermission('users.manage')) {
            throw ValidationException::withMessages([
                'user_id' => 'اجازه حذف عضویت در این شرکت را ندارید.',
            ]);
        }

        DB::transaction(function () use ($companyId, $userId, $actor): void {
            $company = DB::table('companies')
                ->where('id', $companyId)
                ->where('is_active', true)
                ->whereNull('deleted_at')
                ->lockForUpdate()
                ->first();

            if (! $company || (int) $actor->currentCompanyId() !== $companyId) {
                throw ValidationException::withMessages([
                    'company_id' => 'شرکت جاری دیگر فعال یا در دسترس نیست.',
                ]);
            }

            $targetUser = DB::table('users')
                ->where('id', $userId)
                ->lockForUpdate()
                ->first();

            if (! $targetUser) {
                throw ValidationException::withMessages([
                    'user_id' => 'حساب کاربری انتخاب‌شده پیدا نشد.',
                ]);
            }

            $membership = DB::table('company_user')
                ->where('company_id', $companyId)
                ->where('user_id', $userId)
                ->lockForUpdate()
                ->first();

            if (! $membership) {
                throw ValidationException::withMessages([
                    'user_id' => 'عضویت کاربر در شرکت جاری پیدا نشد.',
                ]);
            }

            $hasDirectReports = DB::table('company_user')
                ->where('company_id', $companyId)
                ->where('manager_user_id', $userId)
                ->exists();

            if ($hasDirectReports) {
                throw ValidationException::withMessages([
                    'user_id' => 'ابتدا مدیر مستقیم اعضای زیرمجموعه این کاربر را تغییر دهید.',
                ]);
            }

            $roleIds = DB::table('company_user_role')
                ->where('company_user_id', $membership->id)
                ->pluck('role_id');

            if ($roleIds->isEmpty() && $membership->role_id) {
                $roleIds = collect([(int) $membership->role_id]);
            }

            $isCompanyAdmin = Role::query()
                ->where('company_id', $companyId)
                ->whereIn('id', $roleIds)
                ->where('name', 'admin')
                ->exists();

            if ($isCompanyAdmin) {
                $otherAdminExists = DB::table('company_user as memberships')
                    ->leftJoin('company_user_role as membership_roles', 'membership_roles.company_user_id', '=', 'memberships.id')
                    ->leftJoin('roles as assigned_roles', function ($join) use ($companyId): void {
                        $join->on('assigned_roles.id', '=', 'membership_roles.role_id')
                            ->where('assigned_roles.company_id', '=', $companyId);
                    })
                    ->leftJoin('roles as legacy_roles', function ($join) use ($companyId): void {
                        $join->on('legacy_roles.id', '=', 'memberships.role_id')
                            ->where('legacy_roles.company_id', '=', $companyId);
                    })
                    ->join('users', 'users.id', '=', 'memberships.user_id')
                    ->where('memberships.company_id', $companyId)
                    ->where('memberships.user_id', '<>', $userId)
                    ->where('users.is_active', true)
                    ->whereNull('users.deleted_at')
                    ->where(function ($query): void {
                        $query->where('assigned_roles.name', 'admin')
                            ->orWhere(function ($fallback): void {
                                $fallback->whereNull('membership_roles.id')
                                    ->where('legacy_roles.name', 'admin');
                            });
                    })
                    ->exists();

                if (! $otherAdminExists) {
                    throw ValidationException::withMessages([
                        'user_id' => 'حذف این عضویت مجاز نیست؛ شرکت باید حداقل یک مدیر فعال داشته باشد.',
                    ]);
                }
            }

            AuditEvent::query()->create([
                'company_id' => $companyId,
                'auditable_type' => User::class,
                'auditable_id' => $userId,
                'actor_user_id' => $actor->id,
                'event' => 'membership_removed',
                'old_values' => [
                    'role_ids' => array_map('intval', $roleIds->all()),
                    'manager_user_id' => $membership->manager_user_id,
                    'is_default' => (bool) $membership->is_default,
                ],
                'new_values' => [],
                'metadata' => ['source' => 'company_membership', 'membership_id' => (int) $membership->id],
                'occurred_at' => now(),
            ]);

            DB::table('company_user_role')
                ->where('company_user_id', $membership->id)
                ->delete();

            DB::table('company_user')
                ->where('id', $membership->id)
                ->delete();

            if ((bool) $membership->is_default) {
                DB::table('company_user')
                    ->where('user_id', $userId)
                    ->update([
                        'is_default' => false,
                        'updated_at' => now(),
                    ]);

                $nextDefaultMembership = DB::table('company_user')
                    ->join('companies', 'companies.id', '=', 'company_user.company_id')
                    ->where('company_user.user_id', $userId)
                    ->where('companies.is_active', true)
                    ->whereNull('companies.deleted_at')
                    ->orderBy('company_user.id')
                    ->first(['company_user.id']);

                if ($nextDefaultMembership) {
                    DB::table('company_user')
                        ->where('id', $nextDefaultMembership->id)
                        ->update([
                            'is_default' => true,
                            'updated_at' => now(),
                        ]);
                }
            }
        });
    }
}
