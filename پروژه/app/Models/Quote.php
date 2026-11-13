<?php

namespace App\Models;

use App\Models\Concerns\BelongsToCompany;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Quote extends Model
{
    use BelongsToCompany;
    use SoftDeletes;

    public const STATUS_DRAFT = 'draft';
    public const STATUS_SENT = 'sent';
    public const STATUS_ACCEPTED = 'accepted';
    public const STATUS_REJECTED = 'rejected';
    public const STATUS_CONVERTED = 'converted';

    protected $fillable = [
        'company_id',
        'customer_id',
        'opportunity_id',
        'owner_user_id',
        'number',
        'title',
        'status',
        'valid_until',
        'subtotal',
        'discount_amount',
        'tax_amount',
        'total',
        'currency',
        'notes',
        'accepted_at',
        'converted_at',
    ];

    protected function casts(): array
    {
        return [
            'valid_until' => 'date',
            'subtotal' => 'decimal:0',
            'discount_amount' => 'decimal:0',
            'tax_amount' => 'decimal:0',
            'total' => 'decimal:0',
            'accepted_at' => 'datetime',
            'converted_at' => 'datetime',
        ];
    }

    public static function statusOptions(): array
    {
        return [
            self::STATUS_DRAFT => 'پیش‌نویس',
            self::STATUS_SENT => 'ارسال‌شده',
            self::STATUS_ACCEPTED => 'تأیید مشتری',
            self::STATUS_REJECTED => 'رد شده',
            self::STATUS_CONVERTED => 'تبدیل به سفارش',
        ];
    }

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }

    public function opportunity(): BelongsTo
    {
        return $this->belongsTo(Opportunity::class);
    }

    public function owner(): BelongsTo
    {
        return $this->belongsTo(User::class, 'owner_user_id');
    }

    public function lines(): HasMany
    {
        return $this->hasMany(QuoteLine::class)->orderBy('sort_order');
    }

    public function orders(): HasMany
    {
        return $this->hasMany(Order::class);
    }

    public function recalculateTotals(): void
    {
        $subtotal = (float) $this->lines()->sum('line_total');
        $this->subtotal = $subtotal;
        $this->total = max(0, $subtotal - (float) $this->discount_amount + (float) $this->tax_amount);
        $this->save();
    }

    public static function nextNumber(int $companyId): string
    {
        $year = now()->format('Y');
        $prefix = 'Q-' . $year . '-';
        $last = static::query()
            ->where('company_id', $companyId)
            ->where('number', 'like', $prefix . '%')
            ->orderByDesc('number')
            ->value('number');

        $seq = 1;
        if ($last && preg_match('/(\d+)$/', $last, $m)) {
            $seq = (int) $m[1] + 1;
        }

        return $prefix . str_pad((string) $seq, 4, '0', STR_PAD_LEFT);
    }

    public static function fromOpportunity(Opportunity $opportunity): self
    {
        $quote = new self([
            'company_id' => $opportunity->company_id,
            'customer_id' => $opportunity->customer_id,
            'opportunity_id' => $opportunity->id,
            'owner_user_id' => $opportunity->owner_user_id ?? auth()->id(),
            'number' => self::nextNumber((int) $opportunity->company_id),
            'title' => 'پیش‌فاکتور: ' . $opportunity->title,
            'status' => self::STATUS_DRAFT,
            'valid_until' => now()->addDays(14),
            'notes' => $opportunity->notes,
            'subtotal' => 0,
            'total' => 0,
        ]);
        $quote->save();

        $amount = (float) ($opportunity->amount ?? 0);
        if ($amount > 0 || filled($opportunity->title)) {
            QuoteLine::query()->create([
                'company_id' => $opportunity->company_id,
                'quote_id' => $quote->id,
                'sort_order' => 10,
                'product_type' => 'notopia',
                'title' => $opportunity->title,
                'description' => collect([
                    $opportunity->organization_name,
                    $opportunity->product_interest,
                    $opportunity->qty_approx,
                ])->filter()->implode(' | '),
                'qty' => 1,
                'unit_price' => $amount,
                'line_total' => $amount,
            ]);
            $quote->recalculateTotals();
        }

        return $quote->fresh(['lines', 'customer']);
    }
}
