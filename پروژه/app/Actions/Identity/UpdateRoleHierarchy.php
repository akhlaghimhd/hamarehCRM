<?php

namespace App\Actions\Identity;

use App\Models\Role;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class UpdateRoleHierarchy
{
    /**
     * Set a role's parent and inheritance behavior. Optionally copy a source
     * role's direct permissions as an independent snapshot.
     */
    public function handle(
        Role $role,
        ?int $parentRoleId,
        bool $inheritPermissions,
        ?int $copyPermissionsFromRoleId = null,
    ): void {
        DB::transaction(function () use ($role, $parentRoleId, $inheritPermissions, $copyPermissionsFromRoleId): void {
            if ($parentRoleId !== null) {
                if ($parentRoleId === (int) $role->id || ! Role::query()
                    ->where('company_id', $role->company_id)
                    ->whereKey($parentRoleId)
                    ->exists()) {
                    throw ValidationException::withMessages([
                        'parent_role_id' => 'نقش والد باید متعلق به همین شرکت باشد و نمی‌تواند خود نقش باشد.',
                    ]);
                }

                $cursor = $parentRoleId;
                $visited = [];

                while ($cursor !== null) {
                    if ($cursor === (int) $role->id || in_array($cursor, $visited, true)) {
                        throw ValidationException::withMessages([
                            'parent_role_id' => 'این انتخاب باعث ایجاد چرخه در سلسله‌مراتب نقش‌ها می‌شود.',
                        ]);
                    }

                    $visited[] = $cursor;
                    $nextParentId = Role::query()
                        ->where('company_id', $role->company_id)
                        ->whereKey($cursor)
                        ->value('parent_role_id');

                    $cursor = $nextParentId === null ? null : (int) $nextParentId;
                }
            }

            $role->forceFill([
                'parent_role_id' => $parentRoleId,
                'inherit_permissions' => $inheritPermissions,
            ])->save();

            if ($copyPermissionsFromRoleId !== null) {
                $source = Role::query()
                    ->where('company_id', $role->company_id)
                    ->find($copyPermissionsFromRoleId);

                if (! $source) {
                    throw ValidationException::withMessages([
                        'copy_permissions_from_role_id' => 'نقش منبع برای کپی مجوزها متعلق به شرکت جاری نیست.',
                    ]);
                }

                $sourcePermissions = $source->permissions();

                if (! ($role->is_system && $role->name === 'admin')) {
                    $sourcePermissions->where('permissions.group', '<>', 'سیستم');
                }

                $role->permissions()->sync($sourcePermissions->pluck('permissions.id')->all());
            }
        });
    }
}
