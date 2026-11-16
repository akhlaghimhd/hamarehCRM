<?php

namespace App\\Actions\\Identity;

use App\\Models\\Role;
use App\\Models\\User;
use Illuminate\\Support\\Facades\\DB;
use Illuminate\\Validation\\ValidationException;

class AddExistingUserToCompany
{
    /**
     * Add an existing account to a company without changing its other memberships.
     *
     * @param array<int, int|string> $roleIds
     */
    public function handle(
        int $companyId,
        int $userId,
        array $roleIds,
        ?int $managerUserId,
        User $actor,
    ): void {
        if ((int) $actor->currentCompanyId() !== $companyId || ! $actor->hasPermission('users.manage')) {
            throw ValidationException::withMessages([
                'user_id' => 'اجازه افزودن کاربر به این شرکت را ندارید.',
            ]);
        }

        DB::transaction(function () use ($companyId, $userId, $roleIds, $managerUserId, $actor): void {
            $user = User::query()
                ->whereKey($userId)
                ->where('is_active', true)
                ->lockForUpdate()
                ->first();

            if (! $user) {
                throw ValidationException::withMessages([
                    'user_id' => 'کاربر انتخاب‌شده فعال یا در دسترس نیست.',
                ]);
            }

            if (DB::table('company_user')
                ->where('company_id', $companyId)
                ->where('user_id', $userId)
                ->exists()) {
                throw ValidationException::withMessages([
                    'user_id' => 'این کاربر از قبل عضو شرکت جاری است.',
                ]);
            }

            $roleIds = array_values(array_unique(array_map('intval', $roleIds)));

            if ($roleIds === [] || Role::query()
                ->where('company_id', $companyId)
                ->whereIn('id', $roleIds)
                ->count() !== count($roleIds)) {
                throw ValidationException::withMessages([
                    'role_ids' => 'حداقل یک نقش معتبر از شرکت جاری انتخاب کنید.',
                ]);
            }

            app(UpdateCompanyMembership::class)->assertValidManagerAssignment(
                $companyId,
                $userId,
                $managerUserId,
            );

            $membershipId = DB::table('company_user')->insertGetId([
                'company_id' => $companyId,
                'user_id' => $userId,
                'role_id' => $roleIds[0],
                'manager_user_id' => $managerUserId,
                'is_default' => false,
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            $now = now();
            DB::table('company_user_role')->insert(array_map(
                fn (int $roleId): array => [
                    'company_user_id' => $membershipId,
                    'role_id' => $roleId,
                    'created_at' => $now,
                    'updated_at' => $now,
                ],
                $roleIds,
            ));
        });
    }
}
