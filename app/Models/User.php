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
            ->withPivot(['role_id', 'is_default'])
            ->withTimestamps();
    }

    public function canAccessPanel(Panel $panel): bool
    {
        return (bool) $this->is_active;
    }

    public function currentCompanyId(): ?int
    {
        return session('current_company_id')
            ?? $this->defaultCompany()?->id;
    }

    public function currentRole(): ?Role
    {
        $companyId = $this->currentCompanyId();

        if (! $companyId) {
            return null;
        }

        $roleId = $this->companies()
            ->where('companies.id', $companyId)
            ->first()?->pivot?->role_id;

        return $roleId ? Role::query()->find($roleId) : null;
    }

    public function isCompanyAdmin(): bool
    {
        return $this->currentRole()?->name === 'admin';
    }

    public function hasPermission(string $permission): bool
    {
        if ($this->isCompanyAdmin()) {
            return true;
        }

        $role = $this->currentRole();

        if (! $role) {
            return false;
        }

        return $role->permissions()
            ->where('name', $permission)
            ->exists();
    }

    public function defaultCompany(): ?Company
    {
        return $this->companies()
            ->wherePivot('is_default', true)
            ->first()
            ?? $this->companies()->first();
    }
}
