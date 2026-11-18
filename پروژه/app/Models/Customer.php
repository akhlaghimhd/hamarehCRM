<?php

namespace App\Models;

use App\Models\Concerns\BelongsToCompany;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Customer extends Model
{
    use BelongsToCompany;
    use SoftDeletes;

    public const TYPE_INDIVIDUAL = 'individual';
    public const TYPE_ORGANIZATION = 'organization';

    protected $fillable = [
        'company_id',
        'type',
        'name',
        'national_id',
        'economic_code',
        'registration_number',
        'phone',
        'email',
        'address',
        'notes',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
        ];
    }

    public function contacts(): HasMany
    {
        return $this->hasMany(CustomerContact::class);
    }

    public function opportunities(): HasMany
    {
        return $this->hasMany(Opportunity::class);
    }

    public function invoices(): HasMany
    {
        return $this->hasMany(Invoice::class)->latest('id');
    }

    public function isOrganization(): bool
    {
        return $this->type === self::TYPE_ORGANIZATION;
    }

    /**
     * مانده باز فاکتورهای فعال (صادر/جزئی) برای این مشتری در شرکت جاری.
     */
    public function openBalance(): float
    {
        $rows = $this->invoices()
            ->whereIn('status', [Invoice::STATUS_ISSUED, Invoice::STATUS_PARTIAL])
            ->get(['total', 'paid_amount']);

        return (float) $rows->sum(fn (Invoice $invoice) => max(0, (float) $invoice->total - (float) $invoice->paid_amount));
    }
}
