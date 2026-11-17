<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CompanyBankAccountVersion extends Model
{
    protected $fillable = ['company_id', 'company_bank_account_id', 'version', 'snapshot', 'created_by_user_id'];

    protected function casts(): array
    {
        return ['version' => 'integer', 'snapshot' => 'array'];
    }

    public function bankAccount(): BelongsTo
    {
        return $this->belongsTo(CompanyBankAccount::class, 'company_bank_account_id');
    }
}
