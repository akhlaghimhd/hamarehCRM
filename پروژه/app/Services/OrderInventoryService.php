<?php

namespace App\Services;

use App\Models\CoverSku;
use App\Models\Order;
use App\Models\StockLevel;
use App\Models\StockMovement;
use Illuminate\Support\Facades\DB;

class OrderInventoryService
{
    /**
     * رزرو موجودی جلد برای خطوط سفارش دارای cover_sku در snapshot.
     * idempotent: اگر برای همین سفارش قبلاً رزرو ثبت شده باشد دوباره انجام نمی‌شود.
     */
    public function reserveForOrder(Order $order): int
    {
        return (int) DB::transaction(function () use ($order) {
            $order->loadMissing('lines');

            if ($this->hasMovements($order, StockLevel::TYPE_RESERVE)) {
                return 0;
            }

            $reservedLines = 0;

            foreach ($order->lines as $line) {
                $coverSkuId = data_get($line->config_snapshot, 'cover_sku.id');
                if (! $coverSkuId) {
                    continue;
                }

                $sku = CoverSku::query()->find($coverSkuId);
                if (! $sku) {
                    continue;
                }

                $qty = max(1, (int) $line->qty);
                $level = StockLevel::ensureForItem($sku, $sku->sku.' — '.$sku->name);
                $level->reserve(
                    $qty,
                    'رزرو سفارش '.$order->number.' / خط '.$line->id,
                    $order,
                );
                $reservedLines++;
            }

            return $reservedLines;
        });
    }

    /**
     * آزادسازی تمام رزروهای فعال این سفارش (مثلاً هنگام لغو).
     */
    public function releaseForOrder(Order $order, ?string $reason = null): int
    {
        return (int) DB::transaction(function () use ($order, $reason) {
            $reservedByLevel = StockMovement::query()
                ->where('reference_type', $order->getMorphClass())
                ->where('reference_id', $order->getKey())
                ->where('type', StockLevel::TYPE_RESERVE)
                ->get()
                ->groupBy('stock_level_id')
                ->map(fn ($rows) => (int) $rows->sum('qty'));

            $releasedByLevel = StockMovement::query()
                ->where('reference_type', $order->getMorphClass())
                ->where('reference_id', $order->getKey())
                ->where('type', StockLevel::TYPE_RELEASE)
                ->get()
                ->groupBy('stock_level_id')
                ->map(fn ($rows) => (int) $rows->sum('qty'));

            $count = 0;

            foreach ($reservedByLevel as $levelId => $reservedQty) {
                $open = $reservedQty - (int) ($releasedByLevel[$levelId] ?? 0);
                if ($open <= 0) {
                    continue;
                }

                $level = StockLevel::query()->lockForUpdate()->find($levelId);
                if (! $level) {
                    continue;
                }

                $level->release(
                    $open,
                    $reason ?: ('آزادسازی رزرو سفارش '.$order->number),
                    $order,
                );
                $count++;
            }

            return $count;
        });
    }

    private function hasMovements(Order $order, string $type): bool
    {
        return StockMovement::query()
            ->where('reference_type', $order->getMorphClass())
            ->where('reference_id', $order->getKey())
            ->where('type', $type)
            ->exists();
    }
}
