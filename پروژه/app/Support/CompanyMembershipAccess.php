<?php

namespace App\Support;

use App\Models\Permission;
use App\Models\Role;
use App\Models\User;
use Illuminate\Support\Facades\DB;

class CompanyMembershipAccess
{
    public function isMember(?User $user, int $companyId): bool
    {
        return $user !== null
            && $user->is_active
            && DB::table('company_user')
                ->where('user_id', $user->id)
                ->where('company_id', $companyId)
                ->exists();
    }

    public function isAdmin(?User $user, int $companyId): bool
    {
        $roleIds = $this->membershipRoleIds($user, $companyId);

        return $roleIds->isNotEmpty()
            && Role::query()
                ->where('company_id', $companyId)
                ->whereIn('id', $roleIds)
                ->where('name', 'admin')
                ->exists();
    }

    public function canManage(?User $user, int $companyId): bool
    {
        if (! $this->isMember($user, $companyId)) {
            return false;
        }

        if ($this->isAdmin($user, $companyId)) {
            return true;
        }

        $permissionId = Permission::query()->where('name', 'companies.manage')->value('id');

        if ($permissionId === null) {
            return false;
        }

        return Role::query()
            ->where('company_id', $companyId)
            ->whereIn('id', $this->membershipRoleIds($user, $companyId))
            ->get()
            ->contains(fn (Role $role): bool => $role->effectivePermissionIds()->contains((int) $permissionId));
    }

    /**
     * @return \Illuminate\Support\Collection<int, int>
     */
    private function membershipRoleIds(?User $user, int $companyId): \Illuminate\Support\Collection
    {
        if (! $this->isMember($user, $companyId)) {
            return collect();
        }

        $membership = DB::table('company_user')
            ->where('user_id', $user->id)
            ->where('company_id', $companyId)
            ->first(['id', 'role_id']);

        if (! $membership) {
            return collect();
        }

        $roleIds = DB::table('company_user_role')
            ->where('company_user_id', $membership->id)
            ->pluck('role_id')
            ->map(fn ($id): int => (int) $id);

        if ($roleIds->isEmpty() && $membership->role_id !== null) {
            $roleIds = collect([(int) $membership->role_id]);
        }

        return $roleIds;
    }
}
