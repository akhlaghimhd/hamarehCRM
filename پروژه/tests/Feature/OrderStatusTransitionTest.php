<?php

namespace Tests\Feature;

use App\Models\Company;
use App\Models\Customer;
use App\Models\Order;
use App\Models\Quote;
use App\Models\QuoteLine;
use App\Models\User;
use App\Support\CompanyContext;
use Illuminate\Foundation\Testing\RefreshDatabase;
use InvalidArgumentException;
use Tests\TestCase;

class OrderStatusTransitionTest extends TestCase
{
    use RefreshDatabase;

    public function test_order_can_move_through_fulfillment_and_complete(): void
    {
        $order = $this->makeConfirmedOrder();

        $order->startFulfillment();
        $this->assertSame(Order::STATUS_IN_FULFILLMENT, $order->fresh()->status);

        $order->markCompleted();
        $this->assertSame(Order::STATUS_COMPLETED, $order->fresh()->status);

        $this->assertFalse($order->fresh()->canCancel());
    }

    public function test_order_can_be_cancelled_with_reason(): void
    {
        $order = $this->makeConfirmedOrder();

        $order->cancel('درخواست مشتری');

        $fresh = $order->fresh();
        $this->assertSame(Order::STATUS_CANCELLED, $fresh->status);
        $this->assertStringContainsString('درخواست مشتری', (string) $fresh->notes);
    }

    public function test_invalid_transition_is_rejected(): void
    {
        $order = $this->makeConfirmedOrder();
        $order->markCompleted();

        $this->expectException(InvalidArgumentException::class);
        $order->startFulfillment();
    }

    private function makeConfirmedOrder(): Order
    {
        $company = Company::query()->create([
            'name' => 'Order Co',
            'slug' => 'order-co-'.uniqid(),
            'is_active' => true,
        ]);

        $user = User::factory()->create(['is_active' => true]);
        $user->companies()->attach($company->id, ['is_default' => true]);

        $context = app(CompanyContext::class);

        return $context->runForCompany($company->id, function () use ($company, $user) {
            $customer = Customer::query()->create([
                'type' => Customer::TYPE_ORGANIZATION,
                'name' => 'Buyer',
                'is_active' => true,
            ]);

            $quote = new Quote([
                'company_id' => $company->id,
                'customer_id' => $customer->id,
                'owner_user_id' => $user->id,
                'number' => Quote::nextNumber($company->id),
                'title' => 'Q',
                'status' => Quote::STATUS_DRAFT,
                'subtotal' => 1000,
                'total' => 1000,
            ]);
            $quote->save();

            QuoteLine::query()->create([
                'company_id' => $company->id,
                'quote_id' => $quote->id,
                'sort_order' => 10,
                'product_type' => 'generic',
                'title' => 'Item',
                'qty' => 1,
                'unit_price' => 1000,
                'line_total' => 1000,
            ]);

            return Order::fromQuote($quote->fresh(['lines']));
        });
    }
}
