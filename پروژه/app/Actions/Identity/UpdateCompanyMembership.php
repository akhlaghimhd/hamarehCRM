<?php

namespace App\Actions\Identity;

use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class UpdateCompanyMembership
{
    /**
     * Update roles and reporting manager for one user's membership in a company.
     *
     * @param array<int, int|string> $roleIds
     */
    public function handle(
        int $companyId,
        int $userId,
        array $roleIds,
        ?int $managerUserId,
        ?int $changedByUserId,
    ): void {
        DB::transaction(function () use ($companyId, $userId, $roleIds, $managerUserId, $changedByUserId): void {
            DB::table('companies')
                ->where('id', $companyId)
                ->lockForUpdate()
                ->first();

            $actor = $changedByUserId ? User::query()->find($changedByUserId) : null;

            if (! $actor || ! $actor->is_active
                || (int) $actor->currentCompanyId() !== $companyId
                || ! $actor->hasPermission('users.manage')) {
                throw ValidationException::withMessages([
                    'company_id' => 'اجازه تغییر نقش یا مدیر مستقیم کاربران در این شرکت را ندارید.',
                ]);
            }

            $membership = DB::table('company_user')
                ->where('company_id', $companyId)
                ->where('user_id', $userId)
                ->lockForUpdate()
                ->first();

            if (! $membership) {
                throw ValidationException::withMessages([
                    'company_id' => 'عضویت کاربر در شرکت جاری پیدا نشد.',
                ]);
            }

            $roleIds = array_values(array_unique(array_map('intval', $roleIds)));

            if ($roleIds === [] || DB::table('roles')
                ->where('company_id', $companyId)
                ->whereIn('id', $roleIds)
                ->count() !== count($roleIds)) {
                throw ValidationException::withMessages([
                    'role_ids' => 'تمام نقش‌ها باید متعلق به شرکت جاری باشند.',
                ]);
            }

            $this->assertValidManagerAssignment($companyId, $userId, $managerUserId);
            $this->assertCompanyRetainsActiveAdministrator(
                $companyId,
                $userId,
                $membership,
                $roleIds,
            );

            $oldManagerUserId = $membership->manager_user_id === null
                ? null
                : (int) $membership->manager_user_id;

            if ($oldManagerUserId !== $managerUserId) {
                DB::table('manager_membership_histories')->insert([
                    'company_id' => $companyId,
                    'user_id' => $userId,
                    'old_manager_user_id' => $oldManagerUserId,
                    'new_manager_user_id' => $managerUserId,
                    'changed_by_user_id' => $changedByUserId,
                    'changed_at' => now(),
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }

            DB::table('company_user')
                ->where('id', $membership->id)
                ->update([
                    'role_id' => $roleIds[0] ?? null,
                    'manager_user_id' => $managerUserId,
                    'updated_at' => now(),
                ]);

            DB::table('company_user_role')
                ->where('company_user_id', $membership->id)
                ->delete();

            if ($roleIds !== []) {
                $now = now();
                DB::table('company_user_role')->insert(array_map(
                    fn (int $roleId): array => [
                        'company_user_id' => $membership->id,
                        'role_id' => $roleId,
                        'created_at' => $now,
                        'updated_at' => $now,
                    ],
                    $roleIds,
                ));
            }
        });
    }

    /**
     * Prevent a role change or user deactivation from removing the last active
     * company administrator. The company row is locked by handle() so parallel
     * membership changes serialize this invariant check.
     *
     * @param array<int, int> $newRoleIds
     */
    private function assertCompanyRetainsActiveAdministrator(
        int $companyId,
        int $userId,
        object $membership,
        array $newRoleIds,
    ): void {
        $currentRoleIds = DB::table('company_user_role')
            ->where('company_user_id', $membership->id)
            ->pluck('role_id')
            ->map(fn ($id): int => (int) $id)
            ->all();

        if ($currentRoleIds === [] && $membership->role_id !== null) {
            $currentRoleIds = [(int) $membership->role_id];
        }

        $wasAdministrator = $this->hasAdministratorRole($companyId, $currentRoleIds);
        $willBeAdministrator = $this->hasAdministratorRole($companyId, $newRoleIds);
        $willBeActive = DB::table('users')
            ->where('id', $userId)
            ->where('is_active', true)
            ->whereNull('deleted_at')
            ->exists();

        if ($willBeActive && $willBeAdministrator) {
            return;
        }

        if (! $wasAdministrator && ! $willBeAdministrator) {
            return;
        }

        $anotherActiveAdministratorExists = DB::table('company_user as memberships')
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
            ->where('memberships.user_id', '<>', $userId)
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

        if (! $anotherActiveAdministratorExists) {
            throw ValidationException::withMessages([
                'role_ids' => 'شرکت باید حداقل یک مدیر فعال داشته باشد؛ ابتدا مدیر فعال دیگری تعیین کنید.',
            ]);
        }
    }

    /**
     * @param array<int, int> $roleIds
     */
    private function hasAdministratorRole(int $companyId, array $roleIds): bool
    {
        return $roleIds !== [] && DB::table('roles')
            ->where('company_id', $companyId)
            ->whereIn('id', $roleIds)
            ->where('name', 'admin')
            ->exists();
    }

    public function assertValidManagerAssignment(int $companyId, int $userId, ?int $managerUserId): void
    {
        if ($managerUserId === null) {
            return;
        }

        if ($managerUserId === $userId || ! DB::table('company_user')
            ->join('users', 'users.id', '=', 'company_user.user_id')
            ->where('company_user.company_id', $companyId)
            ->where('company_user.user_id', $managerUserId)
            ->where('users.is_active', true)
            ->whereNull('users.deleted_at')
            ->exists()) {
            throw ValidationException::withMessages([
                'manager_user_id' => 'سرپرست باید کاربر فعال و عضو همین شرکت باشد و نمی‌تواند خود کاربر باشد.',
            ]);
        }

        $cursor = $managerUserId;
        $visited = [];

        while ($cursor !== null) {
            if ($cursor === $userId || in_array($cursor, $visited, true)) {
                throw ValidationException::withMessages([
                    'manager_user_id' => 'این انتخاب باعث ایجاد چرخه در سلسله‌مراتب سرپرستی می‌شود.',
                ]);
            }

            $visited[] = $cursor;
            $nextManagerId = DB::table('company_user')
                ->where('company_id', $companyId)
                ->where('user_id', $cursor)
                ->value('manager_user_id');

            $cursor = $nextManagerId === null ? null : (int) $nextManagerId;
        }
    }

}
