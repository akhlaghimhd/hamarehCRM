<?php

namespace App\Actions\Identity;

use App\Models\AuditEvent;
use App\Models\User;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class UpdateUserAccount
{
    /**
     * Account status is global. A company manager may change it only when
     * the account has no membership in another company.
     *
     * @param array<string, mixed> $data
     */
    public function assertCanChangeStatus(User $target, array $data, User $actor, int $companyId): void
    {
        if (
            ! $actor->is_active
            || (int) $actor->currentCompanyId() !== $companyId
            || ! $actor->hasPermission('users.manage')
        ) {
            throw new AuthorizationException('اجازه تغییر وضعیت حساب در این شرکت را ندارید.');
        }

        if (! DB::table('company_user')
            ->where('company_id', $companyId)
            ->where('user_id', $target->id)
            ->exists()) {
            throw ValidationException::withMessages([
                'is_active' => 'کاربر عضو شرکت جاری نیست.',
            ]);
        }

        if (! array_key_exists('is_active', $data)) {
            return;
        }

        $requestedStatus = (bool) $data['is_active'];

        if ($requestedStatus === (bool) $target->is_active) {
            return;
        }

        if (! $requestedStatus && (int) $target->id === (int) $actor->id) {
            throw ValidationException::withMessages([
                'is_active' => 'غیرفعال‌سازی حساب خودتان از داخل شرکت مجاز نیست.',
            ]);
        }

        $hasOtherCompanyMembership = DB::table('company_user')
            ->where('user_id', $target->id)
            ->where('company_id', '<>', $companyId)
            ->exists();

        if ($hasOtherCompanyMembership) {
            throw ValidationException::withMessages([
                'is_active' => 'تغییر وضعیت حساب سراسری کاربر چندشرکتی از داخل یک شرکت مجاز نیست؛ عضویت شرکت جاری را جداگانه مدیریت کنید.',
            ]);
        }

        if (! $requestedStatus) {
            $company = DB::table('companies')
                ->where('id', $companyId)
                ->where('is_active', true)
                ->whereNull('deleted_at')
                ->lockForUpdate()
                ->first();

            if (! $company || (int) $actor->currentCompanyId() !== $companyId) {
                throw ValidationException::withMessages([
                    'is_active' => 'شرکت جاری دیگر فعال یا در دسترس نیست.',
                ]);
            }

            $hasDirectReports = DB::table('company_user')
                ->where('company_id', $companyId)
                ->where('manager_user_id', $target->id)
                ->exists();

            if ($hasDirectReports) {
                throw ValidationException::withMessages([
                    'is_active' => 'ابتدا مدیر مستقیم اعضای زیرمجموعه این کاربر را تغییر دهید.',
                ]);
            }

            $membership = DB::table('company_user')
                ->where('company_id', $companyId)
                ->where('user_id', $target->id)
                ->lockForUpdate()
                ->first();

            if (! $membership) {
                throw ValidationException::withMessages([
                    'is_active' => 'عضویت کاربر در شرکت جاری دیگر وجود ندارد.',
                ]);
            }

            $roleIds = DB::table('company_user_role')
                ->where('company_user_id', $membership->id)
                ->pluck('role_id');

            if ($roleIds->isEmpty() && $membership->role_id !== null) {
                $roleIds = collect([(int) $membership->role_id]);
            }

            $isCompanyAdmin = DB::table('roles')
                ->where('company_id', $companyId)
                ->whereIn('id', $roleIds)
                ->where('name', 'admin')
                ->exists();

            if ($isCompanyAdmin) {
                $anotherActiveAdminExists = DB::table('company_user as memberships')
                    ->join('users', 'users.id', '=', 'memberships.user_id')
                    ->leftJoin('company_user_role as assigned_roles', 'assigned_roles.company_user_id', '=', 'memberships.id')
                    ->leftJoin('roles as assigned_role', function ($join) use ($companyId): void {
                        $join->on('assigned_role.id', '=', 'assigned_roles.role_id')
                            ->where('assigned_role.company_id', '=', $companyId);
                    })
                    ->leftJoin('roles as legacy_role', function ($join) use ($companyId): void {
                        $join->on('legacy_role.id', '=', 'memberships.role_id')
                            ->where('legacy_role.company_id', '=', $companyId);
                    })
                    ->where('memberships.company_id', $companyId)
                    ->where('memberships.user_id', '<>', $target->id)
                    ->where('users.is_active', true)
                    ->whereNull('users.deleted_at')
                    ->where(function ($query): void {
                        $query->where('assigned_role.name', 'admin')
                            ->orWhere(function ($query): void {
                                $query->whereNull('assigned_roles.id')
                                    ->where('legacy_role.name', 'admin');
                            });
                    })
                    ->exists();

                if (! $anotherActiveAdminExists) {
                    throw ValidationException::withMessages([
                        'is_active' => 'غیرفعال‌سازی مجاز نیست؛ شرکت باید حداقل یک مدیر فعال داشته باشد.',
                    ]);
                }
            }
        }
    }

    public function recordStatusChange(User $target, bool $wasActive, User $actor, int $companyId): void
    {
        if ($wasActive === (bool) $target->is_active) {
            return;
        }

        if ((int) $actor->currentCompanyId() !== $companyId) {
            throw new AuthorizationException('ثبت تغییر وضعیت خارج از شرکت جاری مجاز نیست.');
        }

        AuditEvent::query()->create([
            'company_id' => $companyId,
            'auditable_type' => $target->getMorphClass(),
            'auditable_id' => $target->id,
            'actor_user_id' => $actor->id,
            'event' => 'account_status_changed',
            'old_values' => ['is_active' => $wasActive],
            'new_values' => ['is_active' => (bool) $target->is_active],
            'metadata' => ['source' => 'company_user_management'],
            'occurred_at' => now(),
        ]);
    }
}
