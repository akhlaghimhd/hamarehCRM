<?php

namespace App\Actions\Identity;

use App\Models\Permission;
use App\Models\Role;
use App\Models\User;
use Illuminate\Auth\Access\AuthorizationException;
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
    public function handle(Role $role, array $permissionIds, User $actor): void
    {
        if ($role->company_id === null) {
            throw ValidationException::withMessages([
                'permissions' => 'نقش باید متعلق به یک شرکت مشخص باشد.',
            ]);
        }

        if (
            ! $actor->is_active
            || (int) $actor->currentCompanyId() !== (int) $role->company_id
        ) {
            throw new AuthorizationException('مدیریت مجوزهای نقش خارج از شرکت جاری مجاز نیست.');
        }

        DB::transaction(function () use ($role, $permissionIds, $actor): void {
            $lockedRole = Role::query()
                ->where('company_id', $role->company_id)
                ->whereKey($role->getKey())
                ->lockForUpdate()
                ->firstOrFail();

            $isAdminRole = $lockedRole->name === 'admin';

            if (
                ($isAdminRole && ! $actor->isCompanyAdmin())
                || (! $isAdminRole && ! $actor->hasPermission('roles.manage'))
            ) {
                throw new AuthorizationException('مجوز کافی برای تغییر دسترسی‌های این نقش را ندارید.');
            }

            $query = Permission::query()->whereIn('id', $permissionIds);

            if (! $isAdminRole) {
                $query
                    ->whereNotIn('group', Permission::SYSTEM_GROUPS)
                    ->whereNotIn('name', Permission::SYSTEM_NAMES);
            }

            $allowedPermissionIds = $query
                ->pluck('id')
                ->map(fn ($id): int => (int) $id)
                ->all();

            $lockedRole->permissions()->sync($allowedPermissionIds);
        });
    }
}
