<?php

namespace App\Actions\Identity;

use App\Models\AuditEvent;
use App\Models\Role;
use App\Models\User;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class CreateCompanyRole
{
    /**
     * @param array<string, mixed> $data
     */
    public function handle(array $data, User $actor): Role
    {
        $companyId = $actor->currentCompanyId();
        $name = strtolower(trim((string) ($data['name'] ?? '')));
        $parentRoleId = filled($data['parent_role_id'] ?? null) ? (int) $data['parent_role_id'] : null;
        $copyFromId = filled($data['copy_permissions_from_role_id'] ?? null) ? (int) $data['copy_permissions_from_role_id'] : null;
        $permissionIds = array_values(array_unique(array_map('intval', $data['permissions'] ?? [])));
        $inherit = (bool) ($data['inherit_permissions'] ?? true);

        if (! $actor->is_active || ! $actor->hasPermission('roles.manage') || ! $companyId) {
            throw new AuthorizationException('اجازه ایجاد نقش در شرکت جاری را ندارید.');
        }

        if ($name === 'admin' || ! preg_match('/^[a-z][a-z0-9_-]{0,99}$/', $name)) {
            throw ValidationException::withMessages(['name' => 'شناسه نقش معتبر نیست؛ نام admin رزرو شده است.']);
        }

        if (Role::query()->where('company_id', $companyId)->where('name', $name)->exists()) {
            throw ValidationException::withMessages(['name' => 'این شناسه در شرکت جاری قبلاً استفاده شده است.']);
        }

        foreach ([$parentRoleId, $copyFromId] as $relatedRoleId) {
            if ($relatedRoleId !== null && ! Role::query()->where('company_id', $companyId)->whereKey($relatedRoleId)->exists()) {
                throw ValidationException::withMessages(['parent_role_id' => 'نقش والد یا منبع کپی باید متعلق به شرکت جاری باشد.']);
            }
        }

        return DB::transaction(function () use ($companyId, $name, $data, $actor, $parentRoleId, $copyFromId, $permissionIds, $inherit): Role {
            $role = Role::query()->create([
                'company_id' => $companyId,
                'name' => $name,
                'label' => trim((string) ($data['label'] ?? '')),
                'is_system' => false,
                'inherit_permissions' => $inherit,
            ]);

            app(UpdateRoleHierarchy::class)->handle($role, $parentRoleId, $inherit, $copyFromId, $actor);
            if ($copyFromId === null || $permissionIds !== []) {
                app(SyncCompanyRolePermissions::class)->handle($role, $permissionIds, $actor);
            }

            $role = $role->fresh();
            AuditEvent::query()->create([
                'company_id' => $companyId,
                'auditable_type' => $role->getMorphClass(),
                'auditable_id' => $role->getKey(),
                'actor_user_id' => $actor->id,
                'event' => 'created',
                'old_values' => [],
                'new_values' => [
                    'name' => $role->name,
                    'label' => $role->label,
                    'parent_role_id' => $role->parent_role_id,
                    'inherit_permissions' => (bool) $role->inherit_permissions,
                    'permission_ids' => $role->permissions()->pluck('permissions.id')->map(fn ($id): int => (int) $id)->all(),
                ],
                'metadata' => ['source' => 'role_management'],
                'occurred_at' => now(),
            ]);

            return $role;
        });
    }
}
