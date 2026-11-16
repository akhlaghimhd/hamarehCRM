<?php

namespace App\Actions\Identity;

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

        DB::transaction(function () use ($companyId, $userId): void {
            DB::table('companies')->where('id', $companyId)->lockForUpdate()->first();

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
                            ->orWhere('legacy_roles.name', 'admin');
                    })
                    ->exists();

                if (! $otherAdminExists) {
                    throw ValidationException::withMessages([
                        'user_id' => 'حذف این عضویت مجاز نیست؛ شرکت باید حداقل یک مدیر فعال داشته باشد.',
                    ]);
                }
            }

            DB::table('company_user_role')
                ->where('company_user_id', $membership->id)
                ->delete();

            DB::table('company_user')
                ->where('id', $membership->id)
                ->delete();
        });
    }
}
