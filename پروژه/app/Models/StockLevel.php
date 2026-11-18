<?php

namespace App\Models;

use App\Models\Concerns\BelongsToCompany;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

class StockLevel extends Model
{
    use BelongsToCompany;

    public const TYPE_RECEIPT = 'receipt';
    public const TYPE_RESERVE = 'reserve';
    public const TYPE_RELEASE = 'release';
    public const TYPE_TRANSFER_TO_PRODUCTION = 'transfer_to_production';
    public const TYPE_CONSUME = 'consume';
    public const TYPE_MARK_READY = 'mark_ready';
    public const TYPE_DELIVER = 'deliver';
    public const TYPE_ADJUST = 'adjust';

    protected $fillable = [
        'company_id',
        'item_type',
        'item_id',
        'item_label',
        'qty_on_hand',
        'qty_reserved',
        'qty_in_production',
        'qty_ready',
    ];

    protected function casts(): array
    {
        return [
            'qty_on_hand' => 'integer',
            'qty_reserved' => 'integer',
            'qty_in_production' => 'integer',
            'qty_ready' => 'integer',
        ];
    }

    public static function typeOptions(): array
    {
        return [
            self::TYPE_RECEIPT => 'ورود',
            self::TYPE_RESERVE => 'رزرو',
            self::TYPE_RELEASE => 'آزادسازی رزرو',
            self::TYPE_TRANSFER_TO_PRODUCTION => 'انتقال به تولید',
            self::TYPE_CONSUME => 'مصرف تولید',
            self::TYPE_MARK_READY => 'آماده تحویل',
            self::TYPE_DELIVER => 'تحویل',
            self::TYPE_ADJUST => 'اصلاح',
        ];
    }

    public function item(): MorphTo
    {
        return $this->morphTo();
    }

    public function movements(): HasMany
    {
        return $this->hasMany(StockMovement::class)->latest('occurred_at');
    }

    public function availableQty(): int
    {
        return max(0, (int) $this->qty_on_hand - (int) $this->qty_reserved);
    }

    public static function ensureForItem(Model $item, ?string $label = null): self
    {
        $companyId = app(\App\Support\CompanyContext::class)->requireCompanyId();

        $level = static::query()
            ->where('company_id', $companyId)
            ->where('item_type', $item::class)
            ->where('item_id', $item->getKey())
            ->first();

        if ($level) {
            if ($label && $level->item_label !== $label) {
                $level->update(['item_label' => $label]);
            }

            return $level->fresh();
        }

        return static::query()->create([
            'company_id' => $companyId,
            'item_type' => $item::class,
            'item_id' => $item->getKey(),
            'item_label' => $label ?: (string) ($item->sku ?? $item->name ?? $item->getKey()),
            'qty_on_hand' => 0,
            'qty_reserved' => 0,
            'qty_in_production' => 0,
            'qty_ready' => 0,
        ]);
    }

    public function receive(int $qty, ?string $reason = null, ?Model $reference = null): StockMovement
    {
        $this->assertPositiveQty($qty);

        return $this->applyMovement(self::TYPE_RECEIPT, $qty, function (self $level) use ($qty): void {
            $level->qty_on_hand += $qty;
        }, $reason, $reference);
    }

    public function reserve(int $qty, ?string $reason = null, ?Model $reference = null): StockMovement
    {
        $this->assertPositiveQty($qty);

        return $this->applyMovement(self::TYPE_RESERVE, $qty, function (self $level) use ($qty): void {
            if ($level->availableQty() < $qty) {
                throw new InvalidArgumentException('موجودی آزاد برای رزرو کافی نیست.');
            }
            $level->qty_reserved += $qty;
        }, $reason, $reference);
    }

    public function release(int $qty, ?string $reason = null, ?Model $reference = null): StockMovement
    {
        $this->assertPositiveQty($qty);

        return $this->applyMovement(self::TYPE_RELEASE, $qty, function (self $level) use ($qty): void {
            if ((int) $level->qty_reserved < $qty) {
                throw new InvalidArgumentException('مقدار رزرو برای آزادسازی کافی نیست.');
            }
            $level->qty_reserved -= $qty;
        }, $reason, $reference);
    }

    public function transferToProduction(int $qty, ?string $reason = null, ?Model $reference = null): StockMovement
    {
        $this->assertPositiveQty($qty);

        return $this->applyMovement(self::TYPE_TRANSFER_TO_PRODUCTION, $qty, function (self $level) use ($qty): void {
            if ($level->availableQty() < $qty) {
                throw new InvalidArgumentException('موجودی آزاد برای انتقال به تولید کافی نیست.');
            }
            $level->qty_on_hand -= $qty;
            $level->qty_in_production += $qty;
        }, $reason, $reference);
    }

    public function consume(int $qty, ?string $reason = null, ?Model $reference = null): StockMovement
    {
        $this->assertPositiveQty($qty);

        return $this->applyMovement(self::TYPE_CONSUME, $qty, function (self $level) use ($qty): void {
            if ((int) $level->qty_in_production < $qty) {
                throw new InvalidArgumentException('موجودی در جریان تولید برای مصرف کافی نیست.');
            }
            $level->qty_in_production -= $qty;
        }, $reason, $reference);
    }

    public function markReady(int $qty, ?string $reason = null, ?Model $reference = null): StockMovement
    {
        $this->assertPositiveQty($qty);

        return $this->applyMovement(self::TYPE_MARK_READY, $qty, function (self $level) use ($qty): void {
            if ((int) $level->qty_in_production < $qty) {
                throw new InvalidArgumentException('موجودی در جریان تولید برای آماده تحویل کافی نیست.');
            }
            $level->qty_in_production -= $qty;
            $level->qty_ready += $qty;
        }, $reason, $reference);
    }

    public function deliver(int $qty, ?string $reason = null, ?Model $reference = null): StockMovement
    {
        $this->assertPositiveQty($qty);

        return $this->applyMovement(self::TYPE_DELIVER, $qty, function (self $level) use ($qty): void {
            if ((int) $level->qty_ready < $qty) {
                throw new InvalidArgumentException('موجودی آماده تحویل کافی نیست.');
            }
            $level->qty_ready -= $qty;
        }, $reason, $reference);
    }

    public function adjust(int $delta, string $reason, ?Model $reference = null): StockMovement
    {
        if ($delta === 0) {
            throw new InvalidArgumentException('مقدار اصلاح نمی‌تواند صفر باشد.');
        }

        if (trim($reason) === '') {
            throw new InvalidArgumentException('علت اصلاح الزامی است.');
        }

        return $this->applyMovement(self::TYPE_ADJUST, $delta, function (self $level) use ($delta): void {
            $next = (int) $level->qty_on_hand + $delta;
            if ($next < 0) {
                throw new InvalidArgumentException('موجودی فیزیکی نمی‌تواند منفی شود.');
            }
            if ($next < (int) $level->qty_reserved) {
                throw new InvalidArgumentException('موجودی فیزیکی نمی‌تواند کمتر از مقدار رزرو شده باشد.');
            }
            $level->qty_on_hand = $next;
        }, $reason, $reference);
    }

    private function assertPositiveQty(int $qty): void
    {
        if ($qty <= 0) {
            throw new InvalidArgumentException('مقدار باید بزرگ‌تر از صفر باشد.');
        }
    }

    /**
     * @param  callable(self): void  $mutator
     */
    private function applyMovement(
        string $type,
        int $qty,
        callable $mutator,
        ?string $reason = null,
        ?Model $reference = null,
    ): StockMovement {
        return DB::transaction(function () use ($type, $qty, $mutator, $reason, $reference) {
            /** @var self $level */
            $level = static::query()->whereKey($this->id)->lockForUpdate()->firstOrFail();

            $mutator($level);
            $level->save();

            $movement = StockMovement::query()->create([
                'company_id' => $level->company_id,
                'stock_level_id' => $level->id,
                'type' => $type,
                'qty' => $qty,
                'qty_on_hand_after' => $level->qty_on_hand,
                'qty_reserved_after' => $level->qty_reserved,
                'qty_in_production_after' => $level->qty_in_production,
                'qty_ready_after' => $level->qty_ready,
                'reference_type' => $reference?->getMorphClass(),
                'reference_id' => $reference?->getKey(),
                'reason' => $reason,
                'actor_user_id' => Auth::id(),
                'occurred_at' => now(),
            ]);

            $this->refresh();

            return $movement;
        });
    }
}
