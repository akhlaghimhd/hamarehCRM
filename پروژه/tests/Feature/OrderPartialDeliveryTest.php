<?php

namespace Tests\Feature;

use App\Models\Company;
use App\Models\CoverSku;
use App\Models\Customer;
use App\Models\Order;
use App\Models\Quote;
use App\Models\QuoteLine;
use App\Models\StockLevel;
use App\Models\User;
use App\Services\OrderInventoryService;
use App\Support\CompanyContext;
use Illuminate\Foundation\Testing\RefreshDatabase;
use InvalidArgumentException;
use Tests\TestCase;

class OrderPartialDeliveryTest extends TestCase
{
    use RefreshDatabase;

    public function test_partial_delivery_updates_line_and_stock(): void
    {
        $company = Company::query()->create(['name' => 'DelCo', 'slug' => 'del-'.uniqid(), 'is_active' => true]);
        $user = User::factory()->create(['is_active' => true]);
        $user->companies()->attach($company->id, ['is_default' => true]);

        app(CompanyContext::class)->runForCompany($company->id, function () use ($user, $company) {
            $customer = Customer::query()->create([
                'type' => Customer::TYPE_ORGANIZATION,
                'name' => 'Buyer',
                'is_active' => true,
            ]);

            $sku = CoverSku::query()->create([
                'sku' => 'P-DEL-1',
                'name' => 'جلد تحویل',
                'is_active' => true,
            ]);

            $level = StockLevel::ensureForItem($sku, $sku->sku);
            $level->receive(20, 'موجودی اولیه');

            $quote = Quote::query()->create([
                'customer_id' => $customer->id,
                'owner_user_id' => $user->id,
                'number' => Quote::nextNumber((int) $company->id),
                'title' => 'Q-del',
                'status' => Quote::STATUS_DRAFT,
                'subtotal' => 5000,
                'total' => 5000,
            ]);

            QuoteLine::query()->create([
                'quote_id' => $quote->id,
                'sort_order' => 1,
                'product_type' => 'notopia',
                'title' => 'دفتر',
                'qty' => 5,
                'unit_price' => 1000,
                'line_total' => 5000,
                'config_snapshot' => [
                    'cover_sku' => ['id' => $sku->id, 'name' => $sku->name],
                ],
            ]);

            $order = Order::fromQuote($quote->fresh(['lines']));
            $line = $order->lines()->first();
            $this->assertSame(5.0, (float) $line->qty);
            $this->assertSame(0.0, (float) $line->qty_delivered);

            $level->refresh();
            $this->assertSame(5, (int) $level->qty_reserved);

            // آماده‌سازی مسیر آماده تحویل
            $level->receive(5, 'اضافه برای مسیر تولید');
            $level->transferToProduction(5, 'انتقال تست');
            $level->markReady(5, 'آماده تحویل تست');
            $level->refresh();
            $this->assertSame(5, (int) $level->qty_ready);

            $service = app(OrderInventoryService::class);
            $service->deliverLine($line, 2, 'تحویل اول');

            $line->refresh();
            $level->refresh();

            $this->assertSame(2.0, (float) $line->qty_delivered);
            $this->assertSame(3.0, $line->remainingQty());
            $this->assertFalse($line->isFullyDelivered());
            $this->assertSame(3, (int) $level->qty_ready);
            $this->assertSame(3, (int) $level->qty_reserved);

            $service->deliverLine($line->fresh(), 3, 'تحویل باقیمانده');
            $line->refresh();
            $level->refresh();

            $this->assertTrue($line->isFullyDelivered());
            $this->assertSame(0.0, $line->remainingQty());
            $this->assertSame(0, (int) $level->qty_ready);
            $this->assertSame(0, (int) $level->qty_reserved);
            $this->assertTrue($order->fresh()->isFullyDelivered());

            $this->expectException(InvalidArgumentException::class);
            $service->deliverLine($line->fresh(), 1);
        });
    }
}
