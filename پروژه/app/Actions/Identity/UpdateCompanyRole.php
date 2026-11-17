<?php

namespace App\Actions\Identity;

use App\Models\AuditEvent;
use App\Models\Role;
use App\Models\User;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class UpdateCompanyRole
{
    /**
     * @param array<string, mixed> $data
     */
    public function handle(Role $record, array $data, User $actor): Role
    {
        $companyId = $actor->currentCompanyId();

        if (! $actor->is_active || ! $companyId || (int) $record->company_id !== (int) $companyId) {
            throw new AuthorizationException('ویرایش نقشی خارج از شرکت جاری مجاز نیست.');
        }

        if ($record->name === 'admin' ? ! $actor->isCompanyAdmin() : ! $actor->hasPermission('roles.manage')) {
            throw new AuthorizationException('اجازه ویرایش این نقش را ندارید.');
        }

        $name = strtolower(trim((string) ($data['name'] ?? $record->name)));

        if (($record->is_system || $record->name === 'admin') && $name !== $record->name) {
            throw ValidationException::withMessages(['name' => 'شناسه نقش سیستمی قابل تغییر نیست.']);
        }

        if ($record->name !== 'admin' && $name === 'admin') {
            throw ValidationException::withMessages(['name' => 'نام admin رزرو شده است.']);
        }

        if (! preg_match('/^[a-z][a-z0-9_-]{0,99}$/', $name)) {
            throw ValidationException::withMessages(['name' => 'شناسه نقش باید با حرف انگلیسی کوچک شروع شود.']);
        }

        if (Role::query()->where('company_id', $companyId)->where('name', $name)->where('id', '<>', $record->getKey())->exists()) {
            throw ValidationException::withMessages(['name' => 'این شناسه در شرکت جاری قبلاً استفاده شده است.']);
        }

        $parentRoleId = filled($data['parent_role_id'] ?? null) ? (int) $data['parent_role_id'] : null;
        $inherit = (bool) ($data['inherit_permissions'] ?? false);
        $permissionIds = array_values(array_unique(array_map('intval', $data['permissions'] ?? [])));

        return DB::transaction(function () use ($record, $data, $actor, $companyId, $name, $parentRoleId, $inherit, $permissionIds): Role {
            $role = Role::query()->where('company_id', $companyId)->whereKey($record->getKey())->lockForUpdate()->firstOrFail();
            $oldState = [
                'name' => $role->name,
                'label' => $role->label,
                'parent_role_id' => $role->parent_role_id === null ? null : (int) $role->parent_role_id,
                'inherit_permissions' => (bool) $role->inherit_permissions,
                'permission_ids' => $role->permissions()->pluck('permissions.id')->map(fn ($id): int => (int) $id)->all(),
            ];

            $role->fill([
                'name' => $name,
                'label' => trim((string) ($data['label'] ?? $role->label)),
            ])->save();

            app(UpdateRoleHierarchy::class)->handle($role, $parentRoleId, $inherit, null, $actor);
            app(SyncCompanyRolePermissions::class)->handle($role, $permissionIds, $actor);

            $role = $role->fresh();
            $newState = [
                'name' => $role->name,
                'label' => $role->label,
                'parent_role_id' => $role->parent_role_id === null ? null : (int) $role->parent_role_id,
                'inherit_permissions' => (bool) $role->inherit_permissions,
                'permission_ids' => $role->permissions()->pluck('permissions.id')->map(fn ($id): int => (int) $id)->all(),
            ];

            $oldComparable = $oldState;
            $newComparable = $newState;
            sort($oldComparable['permission_ids']);
            sort($newComparable['permission_ids']);

            if ($oldComparable !== $newComparable) {
                AuditEvent::query()->create([
                    'company_id' => $companyId,
                    'auditable_type' => $role->getMorphClass(),
                    'auditable_id' => $role->getKey(),
                    'actor_user_id' => $actor->id,
                    'event' => 'updated',
                    'old_values' => $oldState,
                    'new_values' => $newState,
                    'metadata' => ['source' => 'role_management'],
                    'occurred_at' => now(),
                ]);
            }

            return $role;
        });
    }
}
