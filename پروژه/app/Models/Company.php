<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Company extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'name',
        'slug',
        'national_id',
        'registration_number',
        'phone',
        'email',
        'address',
        'website',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
        ];
    }

    public function users(): BelongsToMany
    {
        return $this->belongsToMany(User::class)
            ->withPivot(['role_id', 'manager_user_id', 'is_default'])
            ->withTimestamps();
    }

    public function financialIdentities(): HasMany
    {
        return $this->hasMany(CompanyFinancialIdentity::class);
    }

    public function customers(): HasMany
    {
        return $this->hasMany(Customer::class);
    }

    public function settings(): HasMany
    {
        return $this->hasMany(CompanySetting::class);
    }
}
