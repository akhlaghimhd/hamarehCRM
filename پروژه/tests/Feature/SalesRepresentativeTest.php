<?php

namespace Tests\Feature;

use App\Models\Company;
use App\Models\Customer;
use App\Models\Order;
use App\Models\Quote;
use App\Models\QuoteLine;
use App\Models\SalesRepresentative;
use App\Models\User;
use App\Support\CompanyContext;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SalesRepresentativeTest extends TestCase
{
    use RefreshDatabase;

    public function test_representative_is_scoped_to_company(): void
    {
        $companyA = Company::query()->create(['name' => 'A', 'slug' => 'a-'.uniqid(), 'is_active' => true]);
        $companyB = Company::query()->create(['name' => 'B', 'slug' => 'b-'.uniqid(), 'is_active' => true]);
        $user = User::factory()->create(['is_active' => true]);
        $user->companies()->attach($companyA->id, ['is_default' => true]);

        $rep = app(CompanyContext::class)->runForCompany($companyA->id, function () use ($user) {
            return SalesRepresentative::query()->create([
                'type' => SalesRepresentative::TYPE_INTERNAL,
                'name' => 'Rep A',
                'code' => 'R1',
                'user_id' => $user->id,
                'status' => SalesRepresentative::STATUS_ACTIVE,
            ]);
        });

        $this->assertTrue(app(CompanyContext::class)->runForCompany(
            $companyA->id,
            fn () => SalesRepresentative::query()->whereKey($rep->id)->exists(),
        ));
        $this->assertFalse(app(CompanyContext::class)->runForCompany(
            $companyB->id,
            fn () => SalesRepresentative::query()->whereKey($rep->id)->exists(),
        ));
    }

    public function test_order_from_quote_snapshots_attribution(): void
    {
        $company = Company::query()->create(['name' => 'C', 'slug' => 'c-'.uniqid(), 'is_active' => true]);
        $user = User::factory()->create(['is_active' => true]);
        $user->companies()->attach($company->id, ['is_default' => true]);

        $order = app(CompanyContext::class)->runForCompany($company->id, function () use ($user, $company) {
            $customer = Customer::query()->create([
                'type' => Customer::TYPE_ORGANIZATION,
                'name' => 'Buyer',
                'is_active' => true,
            ]);

            $rep = SalesRepresentative::query()->create([
                'type' => SalesRepresentative::TYPE_INTERNAL,
                'name' => 'Seller',
                'code' => 'S1',
                'user_id' => $user->id,
                'status' => SalesRepresentative::STATUS_ACTIVE,
            ]);

            $quote = Quote::query()->create([
                'customer_id' => $customer->id,
                'owner_user_id' => $user->id,
                'sales_representative_id' => $rep->id,
                'number' => Quote::nextNumber((int) $company->id),
                'title' => 'Q',
                'status' => Quote::STATUS_DRAFT,
                'subtotal' => 1000,
                'total' => 1000,
            ]);

            QuoteLine::query()->create([
                'quote_id' => $quote->id,
                'sort_order' => 1,
                'product_type' => 'notopia',
                'title' => 'Item',
                'qty' => 1,
                'unit_price' => 1000,
                'line_total' => 1000,
            ]);

            return Order::fromQuote($quote->fresh(['lines', 'salesRepresentative']));
        });

        $this->assertSame('Seller', $order->sales_attribution_snapshot['name'] ?? null);
        $this->assertNotNull($order->sales_representative_id);
    }
}
