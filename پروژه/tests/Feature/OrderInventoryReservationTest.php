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
use App\Support\CompanyContext;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class OrderInventoryReservationTest extends TestCase
{
    use RefreshDatabase;

    public function test_converting_quote_reserves_cover_sku_stock(): void
    {
        $company = Company::query()->create(['name' => 'InvCo', 'slug' => 'inv-'.uniqid(), 'is_active' => true]);
        $user = User::factory()->create(['is_active' => true]);
        $user->companies()->attach($company->id, ['is_default' => true]);

        app(CompanyContext::class)->runForCompany($company->id, function () use ($user, $company) {
            $customer = Customer::query()->create([
                'type' => Customer::TYPE_ORGANIZATION,
                'name' => 'Buyer',
                'is_active' => true,
            ]);

            $sku = CoverSku::query()->create([
                'sku' => 'P-TEST-1',
                'name' => 'جلد تست',
                'is_active' => true,
            ]);

            $level = StockLevel::ensureForItem($sku, $sku->sku);
            $level->receive(10, 'موجودی اولیه');

            $quote = Quote::query()->create([
                'customer_id' => $customer->id,
                'owner_user_id' => $user->id,
                'number' => Quote::nextNumber((int) $company->id),
                'title' => 'Q-inv',
                'status' => Quote::STATUS_DRAFT,
                'subtotal' => 3000,
                'total' => 3000,
            ]);

            QuoteLine::query()->create([
                'quote_id' => $quote->id,
                'sort_order' => 1,
                'product_type' => 'notopia',
                'title' => 'دفتر',
                'qty' => 3,
                'unit_price' => 1000,
                'line_total' => 3000,
                'config_snapshot' => [
                    'cover_sku' => ['id' => $sku->id, 'name' => $sku->name],
                ],
            ]);

            $order = Order::fromQuote($quote->fresh(['lines']));

            $level->refresh();
            $this->assertSame(3, (int) $level->qty_reserved);
            $this->assertSame(7, $level->availableQty());

            $order->cancel('لغو تست');
            $level->refresh();
            $this->assertSame(0, (int) $level->qty_reserved);
            $this->assertSame(10, $level->availableQty());
        });
    }
}
