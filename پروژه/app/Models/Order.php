<?php

namespace App\Models;

use App\Models\Concerns\BelongsToCompany;
use App\Support\NumberSequence;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

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
        'sales_representative_id',
        'sales_attribution_snapshot',
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
            'sales_attribution_snapshot' => 'array',
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

    public function salesRepresentative(): BelongsTo
    {
        return $this->belongsTo(SalesRepresentative::class);
    }

    public function lines(): HasMany
    {
        return $this->hasMany(OrderLine::class)->orderBy('sort_order');
    }

    public function workPackages(): HasMany
    {
        return $this->hasMany(WorkPackage::class)->latest('id');
    }

    public static function nextNumber(int $companyId): string
    {
        $year = now()->format('Y');

        return app(NumberSequence::class)->next($companyId, 'order', 'O-'.$year.'-');
    }

    public function canStartFulfillment(): bool
    {
        return $this->status === self::STATUS_CONFIRMED;
    }

    public function canComplete(): bool
    {
        return in_array($this->status, [self::STATUS_CONFIRMED, self::STATUS_IN_FULFILLMENT], true);
    }

    public function canCancel(): bool
    {
        return ! in_array($this->status, [self::STATUS_COMPLETED, self::STATUS_CANCELLED], true);
    }

    public function startFulfillment(): self
    {
        if (! $this->canStartFulfillment()) {
            throw new InvalidArgumentException('فقط سفارش تأییدشده را می‌توان وارد مرحله انجام کرد.');
        }

        $this->status = self::STATUS_IN_FULFILLMENT;
        $this->save();

        return $this;
    }

    public function markCompleted(): self
    {
        if (! $this->canComplete()) {
            throw new InvalidArgumentException('این سفارش در وضعیت فعلی قابل تکمیل نیست.');
        }

        $this->status = self::STATUS_COMPLETED;
        $this->save();

        return $this;
    }

    public function cancel(?string $reason = null): self
    {
        if (! $this->canCancel()) {
            throw new InvalidArgumentException('این سفارش قابل لغو نیست.');
        }

        $this->status = self::STATUS_CANCELLED;

        if (filled($reason)) {
            $note = trim((string) $this->notes);
            $addition = 'لغو: '.trim($reason);
            $this->notes = $note === '' ? $addition : $note."\n".$addition;
        }

        $this->save();

        return $this;
    }

    public static function fromQuote(Quote $quote): self
    {
        return DB::transaction(function () use ($quote) {
            $quote = Quote::query()
                ->whereKey($quote->id)
                ->lockForUpdate()
                ->with(['lines', 'salesRepresentative'])
                ->firstOrFail();

            if ($quote->status === Quote::STATUS_CONVERTED) {
                $existing = self::query()
                    ->where('quote_id', $quote->id)
                    ->orderByDesc('id')
                    ->first();

                if ($existing) {
                    return $existing->loadMissing(['lines', 'customer']);
                }

                throw new InvalidArgumentException('این پیش‌فاکتور قبلاً به سفارش تبدیل شده است.');
            }

            if ($quote->lines->isEmpty()) {
                throw new InvalidArgumentException('پیش‌فاکتور بدون خط قابل تبدیل به سفارش نیست.');
            }

            $order = new self([
                'company_id' => $quote->company_id,
                'customer_id' => $quote->customer_id,
                'quote_id' => $quote->id,
                'opportunity_id' => $quote->opportunity_id,
                'owner_user_id' => $quote->owner_user_id,
                'sales_representative_id' => $quote->sales_representative_id,
                'sales_attribution_snapshot' => $quote->salesRepresentative
                    ? $quote->salesRepresentative->toAttributionSnapshot()
                    : null,
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
        });
    }
}
