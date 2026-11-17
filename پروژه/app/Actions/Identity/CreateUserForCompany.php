<?php

namespace App\Actions\Identity;

use App\Models\AuditEvent;
use App\Models\Role;
use App\Models\User;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class CreateUserForCompany
{
    /**
     * @param array<string, mixed> $data
     */
    public function handle(array $data, User $actor): User
    {
        $companyId = $actor->currentCompanyId();
        $roleIds = array_values(array_unique(array_map('intval', $data['role_ids'] ?? [])));
        $managerUserId = filled($data['manager_user_id'] ?? null) ? (int) $data['manager_user_id'] : null;

        if (! $actor->is_active || ! $actor->hasPermission('users.manage') || ! $companyId) {
            throw new AuthorizationException('اجازه ایجاد کاربر در شرکت جاری را ندارید.');
        }

        if ($roleIds === [] || Role::query()->where('company_id', $companyId)->whereIn('id', $roleIds)->count() !== count($roleIds)) {
            throw ValidationException::withMessages(['role_ids' => 'حداقل یک نقش معتبر از شرکت جاری انتخاب کنید.']);
        }

        $assignsAdministratorRole = Role::query()
            ->where('company_id', $companyId)
            ->whereIn('id', $roleIds)
            ->where('name', 'admin')
            ->exists();

        if ($assignsAdministratorRole && ! $actor->isCompanyAdmin()) {
            throw new AuthorizationException('فقط مدیر شرکت می‌تواند نقش مدیر شرکت را به کاربر واگذار کند.');
        }

        return DB::transaction(function () use ($data, $actor, $companyId, $roleIds, $managerUserId): User {
            $userData = $data;
            unset($userData['role_ids'], $userData['manager_user_id']);

            $user = User::query()->create($userData);
            $user->companies()->syncWithoutDetaching([
                $companyId => [
                    'role_id' => $roleIds[0],
                    'is_default' => true,
                ],
            ]);

            AuditEvent::query()->create([
                'company_id' => $companyId,
                'auditable_type' => $user->getMorphClass(),
                'auditable_id' => $user->id,
                'actor_user_id' => $actor->id,
                'event' => 'membership_added',
                'old_values' => [],
                'new_values' => [
                    'name' => $user->name,
                    'username' => $user->username,
                    'email' => $user->email,
                    'role_ids' => $roleIds,
                    'manager_user_id' => $managerUserId,
                    'is_default' => true,
                ],
                'metadata' => ['source' => 'user_creation'],
                'occurred_at' => now(),
            ]);

            app(UpdateCompanyMembership::class)->handle(
                (int) $companyId,
                (int) $user->id,
                $roleIds,
                $managerUserId,
                (int) $actor->id,
            );

            return $user->refresh();
        });
    }
}
