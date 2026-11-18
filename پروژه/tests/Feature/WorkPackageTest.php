<?php

namespace Tests\Feature;

use App\Models\Company;
use App\Models\Customer;
use App\Models\Order;
use App\Models\Quote;
use App\Models\QuoteLine;
use App\Models\User;
use App\Models\WorkPackage;
use App\Support\CompanyContext;
use Illuminate\Foundation\Testing\RefreshDatabase;
use InvalidArgumentException;
use Tests\TestCase;

class WorkPackageTest extends TestCase
{
    use RefreshDatabase;

    public function test_work_package_can_be_created_from_order(): void
    {
        $order = $this->makeOrder();

        $package = app(CompanyContext::class)->runForCompany(
            $order->company_id,
            fn () => WorkPackage::createForOrder($order, [
                'title' => 'بسته تست',
                'execution_mode' => WorkPackage::MODE_OUTSOURCED,
                'contractor_name' => 'پیمانکار الف',
            ]),
        );

        $this->assertSame($order->id, $package->order_id);
        $this->assertSame(WorkPackage::STATUS_DRAFT, $package->status);
        $this->assertSame(WorkPackage::MODE_OUTSOURCED, $package->execution_mode);
        $this->assertStringStartsWith('WP-', $package->number);
    }

    public function test_work_package_lifecycle(): void
    {
        $order = $this->makeOrder();

        $package = app(CompanyContext::class)->runForCompany(
            $order->company_id,
            fn () => WorkPackage::createForOrder($order),
        );

        $package->assign();
        $this->assertSame(WorkPackage::STATUS_ASSIGNED, $package->fresh()->status);

        $package->start();
        $this->assertSame(WorkPackage::STATUS_IN_PROGRESS, $package->fresh()->status);

        $package->markDelivered();
        $this->assertSame(WorkPackage::STATUS_DELIVERED, $package->fresh()->status);

        $package->accept();
        $this->assertSame(WorkPackage::STATUS_ACCEPTED, $package->fresh()->status);
    }

    public function test_cannot_create_package_for_cancelled_order(): void
    {
        $order = $this->makeOrder();
        $order->cancel('تست');

        $this->expectException(InvalidArgumentException::class);

        app(CompanyContext::class)->runForCompany(
            $order->company_id,
            fn () => WorkPackage::createForOrder($order),
        );
    }

    private function makeOrder(): Order
    {
        $company = Company::query()->create([
            'name' => 'Prod Co',
            'slug' => 'prod-co-'.uniqid(),
            'is_active' => true,
        ]);

        $user = User::factory()->create(['is_active' => true]);
        $user->companies()->attach($company->id, ['is_default' => true]);

        return app(CompanyContext::class)->runForCompany($company->id, function () use ($company, $user) {
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
