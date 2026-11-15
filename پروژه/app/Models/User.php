<?php

namespace App\Models;

use Database\Factories\UserFactory;
use Filament\Models\Contracts\FilamentUser;
use Filament\Panel;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

#[Fillable(['name', 'username', 'email', 'password', 'is_active'])]
#[Hidden(['password', 'remember_token'])]
class User extends Authenticatable implements FilamentUser
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, Notifiable, SoftDeletes;

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'is_active' => 'boolean',
        ];
    }

    public function companies(): BelongsToMany
    {
        return $this->belongsToMany(Company::class)
            ->withPivot(['role_id', 'manager_user_id', 'is_default'])
            ->withTimestamps();
    }

    public function canAccessPanel(Panel $panel): bool
    {
        return (bool) $this->is_active;
    }

    public function currentCompanyId(): ?int
    {
        $selectedCompanyId = session('current_company_id');

        if ($selectedCompanyId !== null) {
            return $this->companies()
                ->where('companies.id', $selectedCompanyId)
                ->exists()
                    ? (int) $selectedCompanyId
                    : null;
        }

        return $this->defaultCompany()?->id;
    }

    /**
     * @return Collection<int, Role>
     */
    public function currentRoles(): Collection
    {
        $companyId = $this->currentCompanyId();

        if (! $companyId) {
            return collect();
        }

        $roleIds = DB::table('company_user_role')
            ->join('company_user', 'company_user.id', '=', 'company_user_role.company_user_id')
            ->where('company_user.company_id', $companyId)
            ->where('company_user.user_id', $this->id)
            ->orderBy('company_user_role.id')
            ->pluck('company_user_role.role_id');

        // Keep legacy memberships readable during staged deployments/backfills.
        if ($roleIds->isEmpty()) {
            $legacyRoleId = DB::table('company_user')
                ->where('company_id', $companyId)
                ->where('user_id', $this->id)
                ->value('role_id');

            if ($legacyRoleId) {
                $roleIds = collect([(int) $legacyRoleId]);
            }
        }

        return Role::query()
            ->where('company_id', $companyId)
            ->whereIn('id', $roleIds)
            ->get();
    }

    public function currentRole(): ?Role
    {
        return $this->currentRoles()->first();
    }

    public function isCompanyAdmin(): bool
    {
        return $this->currentRoles()->contains(fn (Role $role): bool => $role->name === 'admin');
    }

    public function hasPermission(string $permission): bool
    {
        if ($this->isCompanyAdmin()) {
            return true;
        }

        $permissionIds = $this->currentRoles()
            ->flatMap(fn (Role $role) => $role->effectivePermissionIds())
            ->unique()
            ->values();

        return Permission::query()
            ->whereIn('id', $permissionIds)
            ->where('name', $permission)
            ->exists();
    }

    public function currentManagerUserId(): ?int
    {
        $companyId = $this->currentCompanyId();

        if (! $companyId) {
            return null;
        }

        $managerId = DB::table('company_user')
            ->where('company_id', $companyId)
            ->where('user_id', $this->id)
            ->value('manager_user_id');

        return $managerId === null ? null : (int) $managerId;
    }

    /**
     * All direct and indirect reports in the selected company.
     *
     * @return Collection<int, int>
     */
    public function managedUserIds(bool $includeSelf = false): Collection
    {
        $companyId = $this->currentCompanyId();

        if (! $companyId) {
            return collect();
        }

        $managed = collect();
        $frontier = collect([$this->id]);

        while ($frontier->isNotEmpty()) {
            $next = DB::table('company_user')
                ->where('company_id', $companyId)
                ->whereIn('manager_user_id', $frontier)
                ->pluck('user_id')
                ->map(fn ($id): int => (int) $id)
                ->diff($managed)
                ->values();

            if ($next->isEmpty()) {
                break;
            }

            $managed = $managed->merge($next);
            $frontier = $next;
        }

        return $includeSelf ? $managed->prepend((int) $this->id)->unique()->values() : $managed;
    }

    public function canViewActivityOwner(int $ownerUserId): bool
    {
        $companyId = $this->currentCompanyId();

        if (! $companyId || ! DB::table('company_user')
            ->where('company_id', $companyId)
            ->where('user_id', $ownerUserId)
            ->exists()) {
            return false;
        }

        if ($this->isCompanyAdmin()) {
            return true;
        }

        if ($ownerUserId === (int) $this->id) {
            return $this->hasPermission('activities.view');
        }

        return $this->hasPermission('team.activities.view')
            && $this->managedUserIds()->contains($ownerUserId);
    }

    public function canManageActivityOwner(int $ownerUserId): bool
    {
        $companyId = $this->currentCompanyId();

        if (! $companyId || ! DB::table('company_user')
            ->where('company_id', $companyId)
            ->where('user_id', $ownerUserId)
            ->exists()) {
            return false;
        }

        if ($this->isCompanyAdmin()) {
            return true;
        }

        if ($ownerUserId === (int) $this->id) {
            return $this->hasPermission('activities.manage');
        }

        return $this->hasPermission('team.activities.manage')
            && $this->managedUserIds()->contains($ownerUserId);
    }

    public function defaultCompany(): ?Company
    {
        return $this->companies()
            ->wherePivot('is_default', true)
            ->first();
    }
}
