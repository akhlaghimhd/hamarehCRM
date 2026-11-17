<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CompanyFinancialIdentityVersion extends Model
{
    protected $fillable = ['company_id', 'company_financial_identity_id', 'version', 'snapshot', 'created_by_user_id'];

    protected function casts(): array
    {
        return ['version' => 'integer', 'snapshot' => 'array'];
    }

    public function financialIdentity(): BelongsTo
    {
        return $this->belongsTo(CompanyFinancialIdentity::class, 'company_financial_identity_id');
    }
}
