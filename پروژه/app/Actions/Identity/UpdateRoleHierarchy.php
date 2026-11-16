<?php

namespace App\Actions\Identity;

use App\Models\Role;
use App\Models\User;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class UpdateRoleHierarchy
{
    /**
     * Set a role's parent and inheritance behavior. Optionally copy a source
     * role's direct permissions as an independent, company-scoped snapshot.
     */
    public function handle(
        Role $role,
        ?int $parentRoleId,
        bool $inheritPermissions,
        ?int $copyPermissionsFromRoleId,
        User $actor,
    ): void {
        if (
            $role->company_id === null
            || ! $actor->is_active
            || (int) $actor->currentCompanyId() !== (int) $role->company_id
        ) {
            throw new AuthorizationException('مدیریت سلسله‌مراتب نقش خارج از شرکت جاری مجاز نیست.');
        }

        DB::transaction(function () use ($role, $parentRoleId, $inheritPermissions, $copyPermissionsFromRoleId, $actor): void {
            $lockedRole = Role::query()
                ->where('company_id', $role->company_id)
                ->whereKey($role->getKey())
                ->lockForUpdate()
                ->firstOrFail();

            $isSystemAdmin = $lockedRole->name === 'admin';

            if (
                ($isSystemAdmin && ! $actor->isCompanyAdmin())
                || (! $isSystemAdmin && ! $actor->hasPermission('roles.manage'))
            ) {
                throw new AuthorizationException('مجوز کافی برای تغییر سلسله‌مراتب نقش را ندارید.');
            }

            if ($parentRoleId !== null) {
                $parentRole = Role::query()
                    ->where('company_id', $lockedRole->company_id)
                    ->whereKey($parentRoleId)
                    ->first();

                if (
                    $parentRoleId === (int) $lockedRole->id
                    || ! $parentRole
                    || $parentRole->name === 'admin'
                ) {
                    throw ValidationException::withMessages([
                        'parent_role_id' => 'نقش والد باید متعلق به همین شرکت باشد؛ نقش مدیر شرکت نمی‌تواند والد نقش دیگری باشد.',
                    ]);
                }

                $cursor = $parentRoleId;
                $visited = [];

                while ($cursor !== null) {
                    if ($cursor === (int) $lockedRole->id || in_array($cursor, $visited, true)) {
                        throw ValidationException::withMessages([
                            'parent_role_id' => 'این انتخاب باعث ایجاد چرخه در سلسله‌مراتب نقش‌ها می‌شود.',
                        ]);
                    }

                    $visited[] = $cursor;
                    $nextParentId = Role::query()
                        ->where('company_id', $lockedRole->company_id)
                        ->whereKey($cursor)
                        ->value('parent_role_id');

                    $cursor = $nextParentId === null ? null : (int) $nextParentId;
                }
            }

            $lockedRole->forceFill([
                'parent_role_id' => $parentRoleId,
                'inherit_permissions' => $inheritPermissions,
            ])->save();

            if ($copyPermissionsFromRoleId !== null) {
                $source = Role::query()
                    ->where('company_id', $lockedRole->company_id)
                    ->find($copyPermissionsFromRoleId);

                if (! $source) {
                    throw ValidationException::withMessages([
                        'copy_permissions_from_role_id' => 'نقش منبع برای کپی مجوزها متعلق به شرکت جاری نیست.',
                    ]);
                }

                $permissionIds = $source->permissions()
                    ->pluck('permissions.id')
                    ->map(fn ($id): int => (int) $id)
                    ->all();

                app(SyncCompanyRolePermissions::class)->handle($lockedRole, $permissionIds, $actor);
            }
        });
    }
}
