<?php

namespace App\Actions\Identity;

use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class UpdateCompanyMembership
{
    /**
     * Update roles and reporting manager for one user's membership in a company.
     *
     * @param array<int, int|string> $roleIds
     */
    public function handle(
        int $companyId,
        int $userId,
        array $roleIds,
        ?int $managerUserId,
        ?int $changedByUserId,
    ): void {
        DB::transaction(function () use ($companyId, $userId, $roleIds, $managerUserId, $changedByUserId): void {
            $actor = $changedByUserId ? User::query()->find($changedByUserId) : null;

            if (! $actor || ! $actor->is_active
                || (int) $actor->currentCompanyId() !== $companyId
                || ! $actor->hasPermission('users.manage')) {
                throw ValidationException::withMessages([
                    'company_id' => 'اجازه تغییر نقش یا مدیر مستقیم کاربران در این شرکت را ندارید.',
                ]);
            }

            $membership = DB::table('company_user')
                ->where('company_id', $companyId)
                ->where('user_id', $userId)
                ->lockForUpdate()
                ->first();

            if (! $membership) {
                throw ValidationException::withMessages([
                    'company_id' => 'عضویت کاربر در شرکت جاری پیدا نشد.',
                ]);
            }

            $roleIds = array_values(array_unique(array_map('intval', $roleIds)));

            if ($roleIds === [] || DB::table('roles')
                ->where('company_id', $companyId)
                ->whereIn('id', $roleIds)
                ->count() !== count($roleIds)) {
                throw ValidationException::withMessages([
                    'role_ids' => 'تمام نقش‌ها باید متعلق به شرکت جاری باشند.',
                ]);
            }

            $this->assertValidManagerAssignment($companyId, $userId, $managerUserId);

            $oldManagerUserId = $membership->manager_user_id === null
                ? null
                : (int) $membership->manager_user_id;

            if ($oldManagerUserId !== $managerUserId) {
                DB::table('manager_membership_histories')->insert([
                    'company_id' => $companyId,
                    'user_id' => $userId,
                    'old_manager_user_id' => $oldManagerUserId,
                    'new_manager_user_id' => $managerUserId,
                    'changed_by_user_id' => $changedByUserId,
                    'changed_at' => now(),
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }

            DB::table('company_user')
                ->where('id', $membership->id)
                ->update([
                    'role_id' => $roleIds[0] ?? null,
                    'manager_user_id' => $managerUserId,
                    'updated_at' => now(),
                ]);

            DB::table('company_user_role')
                ->where('company_user_id', $membership->id)
                ->delete();

            if ($roleIds !== []) {
                $now = now();
                DB::table('company_user_role')->insert(array_map(
                    fn (int $roleId): array => [
                        'company_user_id' => $membership->id,
                        'role_id' => $roleId,
                        'created_at' => $now,
                        'updated_at' => $now,
                    ],
                    $roleIds,
                ));
            }
        });
    }

    public function assertValidManagerAssignment(int $companyId, int $userId, ?int $managerUserId): void
    {
        if ($managerUserId === null) {
            return;
        }

        if ($managerUserId === $userId || ! DB::table('company_user')
            ->join('users', 'users.id', '=', 'company_user.user_id')
            ->where('company_user.company_id', $companyId)
            ->where('company_user.user_id', $managerUserId)
            ->where('users.is_active', true)
            ->whereNull('users.deleted_at')
            ->exists()) {
            throw ValidationException::withMessages([
                'manager_user_id' => 'سرپرست باید کاربر فعال و عضو همین شرکت باشد و نمی‌تواند خود کاربر باشد.',
            ]);
        }

        $cursor = $managerUserId;
        $visited = [];

        while ($cursor !== null) {
            if ($cursor === $userId || in_array($cursor, $visited, true)) {
                throw ValidationException::withMessages([
                    'manager_user_id' => 'این انتخاب باعث ایجاد چرخه در سلسله‌مراتب سرپرستی می‌شود.',
                ]);
            }

            $visited[] = $cursor;
            $nextManagerId = DB::table('company_user')
                ->where('company_id', $companyId)
                ->where('user_id', $cursor)
                ->value('manager_user_id');

            $cursor = $nextManagerId === null ? null : (int) $nextManagerId;
        }
    }

}
