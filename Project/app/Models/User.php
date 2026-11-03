<?php

namespace App\Models;

use App\Support\CurrentCompany;
use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class User extends Authenticatable
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, Notifiable;

    protected $fillable = [
        'name',
        'username',
        'email',
        'mobile',
        'password',
        'is_active',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

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
            ->withPivot(['id', 'is_default'])
            ->withTimestamps();
    }

    public function companyMemberships(): HasMany
    {
        return $this->hasMany(CompanyUser::class);
    }

    public function membershipFor(?int $companyId = null): ?CompanyUser
    {
        $companyId = $companyId ?? CurrentCompany::id();

        if (! $companyId) {
            return null;
        }

        return $this->companyMemberships()
            ->where('company_id', $companyId)
            ->first();
    }

    /**
     * @return list<string>
     */
    public function permissionCodesForCurrentCompany(): array
    {
        $membership = $this->membershipFor();

        if (! $membership) {
            return [];
        }

        return $membership->roles()
            ->with('permissions')
            ->get()
            ->flatMap(fn (Role $role) => $role->permissions->pluck('code'))
            ->unique()
            ->values()
            ->all();
    }

    public function hasPermission(string $code): bool
    {
        return in_array($code, $this->permissionCodesForCurrentCompany(), true);
    }
}
