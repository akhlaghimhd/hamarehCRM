<?php

namespace App\Actions\Identity;

use App\Models\Permission;
use App\Models\Role;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class SyncCompanyRolePermissions
{
    /**
     * Synchronize a company's role permissions without allowing ordinary roles
     * to receive central system permissions.
     *
     * @param array<int, int|string> $permissionIds
     */
    public function handle(Role $role, array $permissionIds): void
    {
        if ($role->company_id === null) {
            throw ValidationException::withMessages([
                'permissions' => 'نقش باید متعلق به یک شرکت مشخص باشد.',
            ]);
        }

        DB::transaction(function () use ($role, $permissionIds): void {
            $lockedRole = Role::query()
                ->where('company_id', $role->company_id)
                ->whereKey($role->getKey())
                ->lockForUpdate()
                ->firstOrFail();

            $query = Permission::query()->whereIn('id', $permissionIds);

            if (! ($lockedRole->is_system && $lockedRole->name === 'admin')) {
                $query->where('group', '<>', 'سیستم');
            }

            $allowedPermissionIds = $query
                ->pluck('id')
                ->map(fn ($id): int => (int) $id)
                ->all();

            $lockedRole->permissions()->sync($allowedPermissionIds);
        });
    }
}
