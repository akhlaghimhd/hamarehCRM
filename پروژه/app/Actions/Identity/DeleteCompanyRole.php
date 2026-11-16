<?php

namespace App\Actions\Identity;

use App\Models\AuditEvent;
use App\Models\AuditEvent;
use App\Models\Role;
use App\Models\User;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class DeleteCompanyRole
{
    public function handle(Role $role, User $actor): bool
    {
        $companyId = $actor->currentCompanyId();

        if (! $actor->is_active || ! $companyId || ! $actor->hasPermission('roles.manage')) {
            throw new AuthorizationException('اجازه حذف نقش را ندارید.');
        }

        return DB::transaction(function () use ($role, $actor, $companyId): bool {
            $company = DB::table('companies')
                ->where('id', $companyId)
                ->where('is_active', true)
                ->whereNull('deleted_at')
                ->lockForUpdate()
                ->first();

            if (! $company || (int) $actor->currentCompanyId() !== $companyId) {
                throw new AuthorizationException('شرکت جاری دیگر فعال یا در دسترس نیست.');
            }

            $lockedRole = Role::query()
                ->where('company_id', $companyId)
                ->whereKey($role->getKey())
                ->lockForUpdate()
                ->firstOrFail();

            if ($lockedRole->is_system) {
                throw ValidationException::withMessages([
                    'role' => 'نقش‌های سیستمی قابل حذف نیستند.',
                ]);
            }

            $isAssigned = DB::table('company_user_role as membership_roles')
                ->join('company_user as memberships', 'memberships.id', '=', 'membership_roles.company_user_id')
                ->where('memberships.company_id', $companyId)
                ->where('membership_roles.role_id', $lockedRole->id)
                ->exists()
                || DB::table('company_user')
                    ->where('company_id', $companyId)
                    ->where('role_id', $lockedRole->id)
                    ->exists();

            if ($isAssigned) {
                throw ValidationException::withMessages([
                    'role' => 'این نقش به عضویت کاربران اختصاص دارد؛ ابتدا نقش کاربران را تغییر دهید.',
                ]);
            }

            if (Role::query()
                ->where('company_id', $companyId)
                ->where('parent_role_id', $lockedRole->id)
                ->exists()) {
                throw ValidationException::withMessages([
                    'role' => 'این نقش والد نقش‌های دیگری است؛ ابتدا سلسله‌مراتب آن‌ها را اصلاح کنید.',
                ]);
            }

            AuditEvent::query()->create([
                'company_id' => $companyId,
                'auditable_type' => $lockedRole->getMorphClass(),
                'auditable_id' => $lockedRole->getKey(),
                'actor_user_id' => $actor->id,
                'event' => 'deleted',
                'old_values' => [
                    'name' => $lockedRole->name,
                    'label' => $lockedRole->label,
                    'parent_role_id' => $lockedRole->parent_role_id,
                    'inherit_permissions' => (bool) $lockedRole->inherit_permissions,
                    'permission_ids' => $lockedRole->permissions()->pluck('permissions.id')->map(fn ($id): int => (int) $id)->all(),
                ],
                'new_values' => [],
                'metadata' => ['source' => 'role_management'],
                'occurred_at' => now(),
            ]);

            $permissionIds = $lockedRole->permissions()
                ->pluck('permissions.id')
                ->map(fn ($id): int => (int) $id)
                ->values()
                ->all();

            AuditEvent::query()->create([
                'company_id' => $companyId,
                'auditable_type' => $lockedRole->getMorphClass(),
                'auditable_id' => $lockedRole->id,
                'actor_user_id' => $actor->id,
                'event' => 'role_deleted',
                'old_values' => [
                    'name' => $lockedRole->name,
                    'label' => $lockedRole->label,
                    'parent_role_id' => $lockedRole->parent_role_id,
                    'inherit_permissions' => (bool) $lockedRole->inherit_permissions,
                    'permission_ids' => $permissionIds,
                ],
                'new_values' => [],
                'metadata' => ['source' => 'company_role_management'],
                'occurred_at' => now(),
            ]);

            $lockedRole->delete();

            return true;
        });
    }
}
