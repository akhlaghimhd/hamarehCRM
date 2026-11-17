<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class CompanyFinancialIdentity extends Model
{
    protected $fillable = [
        'company_id', 'type', 'legal_name', 'national_id', 'economic_code',
        'registration_number', 'phone', 'email', 'postal_code', 'address',
        'website', 'version', 'is_active',
    ];

    protected function casts(): array
    {
        return ['version' => 'integer', 'is_active' => 'boolean'];
    }

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    public function bankAccounts(): HasMany
    {
        return $this->hasMany(CompanyBankAccount::class, 'company_financial_identity_id');
    }

    public function versions(): HasMany
    {
        return $this->hasMany(CompanyFinancialIdentityVersion::class, 'company_financial_identity_id');
    }
}
