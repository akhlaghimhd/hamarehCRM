<?php

namespace App\Actions\Identity;

use App\Models\AuditEvent;
use App\Models\Role;
use App\Models\User;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class UpdateUserForCompany
{
    /**
     * @param array<string, mixed> $data
     */
    public function handle(User $target, array $data, User $actor): User
    {
        $companyId = $actor->currentCompanyId();
        $roleIds = array_values(array_unique(array_map('intval', $data['role_ids'] ?? [])));
        $managerUserId = filled($data['manager_user_id'] ?? null) ? (int) $data['manager_user_id'] : null;

        if (! $companyId || ! $actor->is_active || ! $actor->hasPermission('users.manage')
            || ! $target->companies()->where('companies.id', $companyId)->exists()) {
            throw new AuthorizationException('اجازه ویرایش این کاربر در شرکت جاری را ندارید.');
        }

        if ($roleIds === [] || Role::query()->where('company_id', $companyId)->whereIn('id', $roleIds)->count() !== count($roleIds)) {
            throw ValidationException::withMessages(['role_ids' => 'حداقل یک نقش معتبر از شرکت جاری انتخاب کنید.']);
        }

        return DB::transaction(function () use ($target, $data, $actor, $companyId, $roleIds, $managerUserId): User {
            $lockedTarget = User::query()->whereKey($target->getKey())->lockForUpdate()->firstOrFail();
            $account = app(UpdateUserAccount::class);

            $account->assertCanChangeStatus($lockedTarget, $data, $actor, (int) $companyId);
            app(UpdateCompanyMembership::class)->assertValidManagerAssignment((int) $companyId, (int) $lockedTarget->id, $managerUserId);

            $wasActive = (bool) $lockedTarget->is_active;
            $oldProfile = [
                'name' => $lockedTarget->name,
                'username' => $lockedTarget->username,
                'email' => $lockedTarget->email,
            ];

            $profileData = $data;
            unset($profileData['role_ids'], $profileData['manager_user_id']);
            $lockedTarget->fill($profileData)->save();

            $account->recordStatusChange($lockedTarget, $wasActive, $actor, (int) $companyId);

            $newProfile = [
                'name' => $lockedTarget->name,
                'username' => $lockedTarget->username,
                'email' => $lockedTarget->email,
            ];
            $oldChanged = [];
            $newChanged = [];

            foreach ($newProfile as $key => $value) {
                if (($oldProfile[$key] ?? null) !== $value) {
                    $oldChanged[$key] = $oldProfile[$key] ?? null;
                    $newChanged[$key] = $value;
                }
            }

            if ($newChanged !== []) {
                AuditEvent::query()->create([
                    'company_id' => $companyId,
                    'auditable_type' => $lockedTarget->getMorphClass(),
                    'auditable_id' => $lockedTarget->id,
                    'actor_user_id' => $actor->id,
                    'event' => 'profile_updated',
                    'old_values' => $oldChanged,
                    'new_values' => $newChanged,
                    'metadata' => ['source' => 'company_user_management'],
                    'occurred_at' => now(),
                ]);
            }

            app(UpdateCompanyMembership::class)->handle(
                (int) $companyId,
                (int) $lockedTarget->id,
                $roleIds,
                $managerUserId,
                (int) $actor->id,
            );

            return $lockedTarget->refresh();
        });
    }
}
