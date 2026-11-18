<?php

namespace Tests\Feature;

use App\Models\Company;
use App\Models\Customer;
use App\Models\Opportunity;
use App\Models\Order;
use App\Models\Quote;
use App\Models\User;
use App\Support\CompanyContext;
use Illuminate\Foundation\Testing\RefreshDatabase;
use InvalidArgumentException;
use Tests\TestCase;

class QuoteOrderConversionTest extends TestCase
{
    use RefreshDatabase;

    public function test_quote_can_be_created_from_opportunity_with_product_interest_array(): void
    {
        [$company, $user, $customer, $opportunity] = $this->seedPath();

        $quote = app(CompanyContext::class)->runForCompany(
            $company->id,
            fn () => Quote::fromOpportunity($opportunity),
        );

        $this->assertSame($opportunity->id, $quote->opportunity_id);
        $this->assertSame($customer->id, $quote->customer_id);
        $this->assertSame(Quote::STATUS_DRAFT, $quote->status);
        $this->assertNotEmpty($quote->number);
        $this->assertGreaterThanOrEqual(1, $quote->lines->count());
        $this->assertSame(1_500_000.0, (float) $quote->total);
    }

    public function test_quote_to_order_is_atomic_and_idempotent(): void
    {
        [$company, $user, $customer, $opportunity] = $this->seedPath();
        $context = app(CompanyContext::class);

        $quote = $context->runForCompany(
            $company->id,
            fn () => Quote::fromOpportunity($opportunity),
        );

        $order = $context->runForCompany(
            $company->id,
            fn () => Order::fromQuote($quote),
        );

        $this->assertSame($quote->id, $order->quote_id);
        $this->assertSame(Order::STATUS_CONFIRMED, $order->status);
        $this->assertSame((float) $quote->total, (float) $order->total);
        $this->assertSame($quote->lines->count(), $order->lines->count());

        $quote->refresh();
        $this->assertSame(Quote::STATUS_CONVERTED, $quote->status);
        $this->assertNotNull($quote->converted_at);

        $again = $context->runForCompany(
            $company->id,
            fn () => Order::fromQuote($quote->fresh(['lines'])),
        );

        $this->assertSame($order->id, $again->id);
        $this->assertSame(1, Order::query()->where('quote_id', $quote->id)->count());
    }

    public function test_empty_quote_cannot_convert_to_order(): void
    {
        [$company, $user, $customer] = $this->seedPath(createOpportunity: false);

        $context = app(CompanyContext::class);

        $quote = $context->runForCompany($company->id, function () use ($company, $customer, $user) {
            $quote = new Quote([
                'company_id' => $company->id,
                'customer_id' => $customer->id,
                'owner_user_id' => $user->id,
                'number' => Quote::nextNumber($company->id),
                'title' => 'Empty quote',
                'status' => Quote::STATUS_DRAFT,
                'subtotal' => 0,
                'total' => 0,
            ]);
            $quote->save();

            return $quote;
        });

        $this->expectException(InvalidArgumentException::class);

        $context->runForCompany(
            $company->id,
            fn () => Order::fromQuote($quote),
        );
    }

    /**
     * @return array{Company, User, Customer, Opportunity|null}
     */
    private function seedPath(bool $createOpportunity = true): array
    {
        $company = Company::query()->create([
            'name' => 'Sales Co',
            'slug' => 'sales-co-'.uniqid(),
            'is_active' => true,
        ]);

        $user = User::factory()->create(['is_active' => true]);
        $user->companies()->attach($company->id, ['is_default' => true]);

        $context = app(CompanyContext::class);

        $customer = $context->runForCompany($company->id, fn () => Customer::query()->create([
            'type' => Customer::TYPE_ORGANIZATION,
            'name' => 'Acme Org',
            'is_active' => true,
        ]));

        $opportunity = null;
        if ($createOpportunity) {
            $opportunity = $context->runForCompany($company->id, fn () => Opportunity::query()->create([
                'company_id' => $company->id,
                'customer_id' => $customer->id,
                'owner_user_id' => $user->id,
                'title' => 'فرصت تست تبدیل',
                'organization_name' => 'Acme Org',
                'status' => Opportunity::STATUS_ACTIVE,
                'amount' => 1_500_000,
                'product_interest' => ['notopia', 'cheleva'],
                'qty_approx' => '500',
                'notes' => 'یادداشت تست',
            ]));
        }

        $this->actingAs($user);
        session(['current_company_id' => $company->id]);

        return [$company, $user, $customer, $opportunity];
    }
}
