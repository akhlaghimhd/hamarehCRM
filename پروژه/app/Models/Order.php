<?php

namespace App\Models;

use App\Models\Concerns\BelongsToCompany;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Order extends Model
{
    use BelongsToCompany;
    use SoftDeletes;

    public const STATUS_DRAFT = 'draft';
    public const STATUS_CONFIRMED = 'confirmed';
    public const STATUS_IN_FULFILLMENT = 'in_fulfillment';
    public const STATUS_COMPLETED = 'completed';
    public const STATUS_CANCELLED = 'cancelled';

    protected $fillable = [
        'company_id',
        'customer_id',
        'quote_id',
        'opportunity_id',
        'owner_user_id',
        'number',
        'title',
        'status',
        'subtotal',
        'discount_amount',
        'tax_amount',
        'total',
        'currency',
        'notes',
        'confirmed_at',
    ];

    protected function casts(): array
    {
        return [
            'subtotal' => 'decimal:0',
            'discount_amount' => 'decimal:0',
            'tax_amount' => 'decimal:0',
            'total' => 'decimal:0',
            'confirmed_at' => 'datetime',
        ];
    }

    public static function statusOptions(): array
    {
        return [
            self::STATUS_DRAFT => 'پیش‌نویس',
            self::STATUS_CONFIRMED => 'تأیید شده',
            self::STATUS_IN_FULFILLMENT => 'در حال انجام',
            self::STATUS_COMPLETED => 'تکمیل',
            self::STATUS_CANCELLED => 'لغو',
        ];
    }

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }

    public function quote(): BelongsTo
    {
        return $this->belongsTo(Quote::class);
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
        return $this->hasMany(OrderLine::class)->orderBy('sort_order');
    }

    public static function nextNumber(int $companyId): string
    {
        $year = now()->format('Y');
        $prefix = 'O-' . $year . '-';
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

    public static function fromQuote(Quote $quote): self
    {
        $order = new self([
            'company_id' => $quote->company_id,
            'customer_id' => $quote->customer_id,
            'quote_id' => $quote->id,
            'opportunity_id' => $quote->opportunity_id,
            'owner_user_id' => $quote->owner_user_id,
            'number' => self::nextNumber((int) $quote->company_id),
            'title' => $quote->title,
            'status' => self::STATUS_CONFIRMED,
            'subtotal' => $quote->subtotal,
            'discount_amount' => $quote->discount_amount,
            'tax_amount' => $quote->tax_amount,
            'total' => $quote->total,
            'currency' => $quote->currency,
            'notes' => $quote->notes,
            'confirmed_at' => now(),
        ]);
        $order->save();

        foreach ($quote->lines as $line) {
            OrderLine::query()->create([
                'company_id' => $quote->company_id,
                'order_id' => $order->id,
                'sort_order' => $line->sort_order,
                'product_type' => $line->product_type,
                'product_model_id' => $line->product_model_id,
                'title' => $line->title,
                'description' => $line->description,
                'config_snapshot' => $line->config_snapshot,
                'qty' => $line->qty,
                'unit_price' => $line->unit_price,
                'line_total' => $line->line_total,
            ]);
        }

        $quote->status = Quote::STATUS_CONVERTED;
        $quote->converted_at = now();
        $quote->accepted_at = $quote->accepted_at ?? now();
        $quote->save();

        return $order->fresh(['lines', 'customer']);
    }
}
