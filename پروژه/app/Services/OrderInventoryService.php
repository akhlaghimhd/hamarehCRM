<?php

namespace App\Services;

use App\Models\CoverSku;
use App\Models\Order;
use App\Models\OrderLine;
use App\Models\StockLevel;
use App\Models\StockMovement;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

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

    /**
     * تحویل جزئی یک خط سفارش از موجودی آماده تحویل.
     * - qty_delivered خط را افزایش می‌دهد
     * - از qty_ready کم می‌کند (TYPE_DELIVER)
     * - معادل همان مقدار از رزرو باز سفارش آزاد می‌شود تا ظرفیت قابل سفارش برگردد
     */
    public function deliverLine(OrderLine $line, int $qty, ?string $reason = null): void
    {
        if ($qty <= 0) {
            throw new InvalidArgumentException('مقدار تحویل باید بزرگ‌تر از صفر باشد.');
        }

        DB::transaction(function () use ($line, $qty, $reason) {
            /** @var OrderLine $line */
            $line = OrderLine::query()->whereKey($line->id)->lockForUpdate()->firstOrFail();
            $order = Order::query()->whereKey($line->order_id)->lockForUpdate()->firstOrFail();

            if (in_array($order->status, [Order::STATUS_CANCELLED, Order::STATUS_DRAFT], true)) {
                throw new InvalidArgumentException('این سفارش در وضعیت فعلی قابل تحویل نیست.');
            }

            $remaining = (int) floor($line->remainingQty());
            if ($qty > $remaining) {
                throw new InvalidArgumentException(
                    'مقدار تحویل ('.$qty.') بیشتر از مانده خط ('.$remaining.') است.'
                );
            }

            $coverSkuId = data_get($line->config_snapshot, 'cover_sku.id');
            if (! $coverSkuId) {
                // خط بدون قلم انباری: فقط مانده سفارش را ثبت کن
                $line->qty_delivered = (float) $line->qty_delivered + $qty;
                $line->save();

                return;
            }

            $sku = CoverSku::query()->find($coverSkuId);
            if (! $sku) {
                throw new InvalidArgumentException('جلد مرتبط با این خط یافت نشد.');
            }

            $level = StockLevel::ensureForItem($sku, $sku->sku.' — '.$sku->name);
            $level = StockLevel::query()->whereKey($level->id)->lockForUpdate()->firstOrFail();

            $note = $reason ?: ('تحویل جزئی سفارش '.$order->number.' / خط '.$line->id);

            // تحویل از موجودی آماده
            $level->deliver($qty, $note, $order);

            // آزادسازی رزرو متناسب (اگر هنوز رزرو باز باشد)
            $openReserved = $this->openReservedQtyForLevel($order, (int) $level->id);
            if ($openReserved > 0) {
                $toRelease = min($openReserved, $qty);
                $level->release($toRelease, 'آزادسازی رزرو به‌خاطر تحویل جزئی — '.$order->number, $order);
            }

            $line->qty_delivered = (float) $line->qty_delivered + $qty;
            $line->save();
        });
    }

    private function openReservedQtyForLevel(Order $order, int $stockLevelId): int
    {
        $reserved = (int) StockMovement::query()
            ->where('reference_type', $order->getMorphClass())
            ->where('reference_id', $order->getKey())
            ->where('stock_level_id', $stockLevelId)
            ->where('type', StockLevel::TYPE_RESERVE)
            ->sum('qty');

        $released = (int) StockMovement::query()
            ->where('reference_type', $order->getMorphClass())
            ->where('reference_id', $order->getKey())
            ->where('stock_level_id', $stockLevelId)
            ->where('type', StockLevel::TYPE_RELEASE)
            ->sum('qty');

        return max(0, $reserved - $released);
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
