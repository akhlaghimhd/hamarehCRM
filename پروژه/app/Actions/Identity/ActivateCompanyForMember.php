<?php

namespace App\Actions\Identity;

use App\Models\Company;
use App\Models\Role;
use App\Models\User;
use App\Support\CompanyContext;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class ActivateCompanyForMember
{
    public function hasRecoverableCompany(User $actor): bool
    {
        if (! $actor->is_active) {
            return false;
        }

        $memberships = DB::table('company_user as cu')
            ->join('companies as c', 'c.id', '=', 'cu.company_id')
            ->where('cu.user_id', $actor->id)
            ->where(function ($query): void {
                $query->where('c.is_active', false)
                    ->orWhereNotNull('c.deleted_at');
            })
            ->get([
                'cu.id as membership_id',
                'cu.company_id',
                'cu.role_id',
            ]);

        foreach ($memberships as $membership) {
            if ($this->hasAdminRole(
                (int) $membership->company_id,
                (int) $membership->membership_id,
                $membership->role_id === null ? null : (int) $membership->role_id,
            )) {
                return true;
            }
        }

        return false;
    }

    public function canActivate(User $actor, int $companyId): bool
    {
        if (! $actor->is_active) {
            return false;
        }

        $membership = DB::table('company_user')
            ->where('company_id', $companyId)
            ->where('user_id', $actor->id)
            ->first();

        $company = DB::table('companies')
            ->where('id', $companyId)
            ->first(['is_active', 'deleted_at']);

        if (! $membership || ! $company || ((bool) $company->is_active && $company->deleted_at === null)) {
            return false;
        }

        return $this->hasAdminRole(
            $companyId,
            (int) $membership->id,
            $membership->role_id === null ? null : (int) $membership->role_id,
        );
    }

    public function handle(int $companyId, User $actor): Company
    {
        if (! $actor->is_active) {
            throw new AuthorizationException('حساب کاربری غیرفعال است.');
        }

        $company = DB::transaction(function () use ($companyId, $actor): Company {
            $membership = DB::table('company_user')
                ->where('company_id', $companyId)
                ->where('user_id', $actor->id)
                ->lockForUpdate()
                ->first();

            if (! $membership) {
                throw ValidationException::withMessages([
                    'company' => 'شما عضو این شرکت نیستید.',
                ]);
            }

            if (! $this->hasAdminRole(
                $companyId,
                (int) $membership->id,
                $membership->role_id === null ? null : (int) $membership->role_id,
            )) {
                throw new AuthorizationException('فقط مدیر همان شرکت می‌تواند دسترسی آن را دوباره فعال کند.');
            }

            $company = Company::withTrashed()
                ->whereKey($companyId)
                ->lockForUpdate()
                ->firstOrFail();

            $wasDeleted = $company->trashed();
            $wasActive = (bool) $company->is_active;

            if ($wasDeleted || ! $wasActive) {
                $oldValues = [
                    'is_active' => $wasActive,
                    'deleted_at' => $company->deleted_at?->toISOString(),
                ];

                if ($wasDeleted) {
                    $company->restore();
                }

                $company->forceFill(['is_active' => true])->save();

                // During recovery there is deliberately no active Company Context.
                // Insert the audit row with the explicitly authorized company_id.
                $now = now();

                DB::table('audit_events')->insert([
                    'company_id' => $companyId,
                    'auditable_type' => $company->getMorphClass(),
                    'auditable_id' => $company->getKey(),
                    'actor_user_id' => $actor->id,
                    'event' => $wasDeleted ? 'restored' : 'activated',
                    'old_values' => json_encode($oldValues, JSON_THROW_ON_ERROR | JSON_UNESCAPED_UNICODE),
                    'new_values' => json_encode([
                        'is_active' => true,
                        'deleted_at' => null,
                    ], JSON_THROW_ON_ERROR | JSON_UNESCAPED_UNICODE),
                    'metadata' => json_encode(['source' => 'company_recovery'], JSON_THROW_ON_ERROR),
                    'occurred_at' => $now,
                    'created_at' => $now,
                    'updated_at' => $now,
                ]);
            }

            return $company->fresh();
        });

        app(CompanyContext::class)->selectForUser($actor, $companyId);

        return $company;
    }

    private function hasAdminRole(int $companyId, int $membershipId, ?int $legacyRoleId): bool
    {
        $roleIds = DB::table('company_user_role')
            ->where('company_user_id', $membershipId)
            ->pluck('role_id');

        // Respect the staged legacy fallback used by User::currentRoles().
        if ($roleIds->isEmpty() && $legacyRoleId !== null) {
            $roleIds = collect([$legacyRoleId]);
        }

        return $roleIds->isNotEmpty()
            && Role::query()
                ->where('company_id', $companyId)
                ->whereIn('id', $roleIds)
                ->where('name', 'admin')
                ->exists();
    }
}
