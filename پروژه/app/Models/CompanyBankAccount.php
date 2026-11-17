<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class CompanyBankAccount extends Model
{
    protected $fillable = [
        'company_id', 'company_financial_identity_id', 'label', 'account_holder',
        'bank_name', 'account_number', 'iban', 'card_number', 'branch',
        'version', 'is_default', 'is_active',
    ];

    protected function casts(): array
    {
        return [
            'version' => 'integer',
            'is_default' => 'boolean',
            'is_active' => 'boolean',
        ];
    }

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    public function financialIdentity(): BelongsTo
    {
        return $this->belongsTo(CompanyFinancialIdentity::class, 'company_financial_identity_id');
    }

    public function versions(): HasMany
    {
        return $this->hasMany(CompanyBankAccountVersion::class, 'company_bank_account_id');
    }
}
