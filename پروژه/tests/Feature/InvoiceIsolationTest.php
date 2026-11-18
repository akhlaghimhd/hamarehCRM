<?php

namespace Tests\Feature;

use App\Models\Company;
use App\Models\CompanyFinancialIdentity;
use App\Models\Customer;
use App\Models\Invoice;
use App\Models\Order;
use App\Models\Payment;
use App\Models\Quote;
use App\Models\QuoteLine;
use App\Models\User;
use App\Support\CompanyContext;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class InvoiceIsolationTest extends TestCase
{
    use RefreshDatabase;

    public function test_invoice_is_scoped_to_company(): void
    {
        [$companyA, $orderA] = $this->makeOrderForCompany('A', 1000);
        [$companyB] = $this->makeOrderForCompany('B', 2000);

        $invoice = app(CompanyContext::class)->runForCompany(
            $companyA->id,
            fn () => Invoice::fromOrder($orderA, Invoice::IDENTITY_OFFICIAL),
        );

        $visibleInA = app(CompanyContext::class)->runForCompany(
            $companyA->id,
            fn () => Invoice::query()->whereKey($invoice->id)->exists(),
        );

        $visibleInB = app(CompanyContext::class)->runForCompany(
            $companyB->id,
            fn () => Invoice::query()->whereKey($invoice->id)->exists(),
        );

        $this->assertTrue($visibleInA);
        $this->assertFalse($visibleInB);
    }

    public function test_customer_open_balance_sums_unpaid_invoices(): void
    {
        [$company, $order] = $this->makeOrderForCompany('Bal', 1500);

        $context = app(CompanyContext::class);

        $invoice = $context->runForCompany(
            $company->id,
            fn () => Invoice::fromOrder($order, Invoice::IDENTITY_OFFICIAL),
        );

        $context->runForCompany($company->id, function () use ($invoice) {
            Payment::recordForInvoice($invoice, ['amount' => 500]);
        });

        $balance = $context->runForCompany(
            $company->id,
            fn () => $invoice->customer->fresh()->openBalance(),
        );

        $this->assertSame(1000.0, $balance);
    }

    /**
     * @return array{Company, Order}
     */
    private function makeOrderForCompany(string $suffix, float $total): array
    {
        $company = Company::query()->create([
            'name' => 'Co '.$suffix,
            'slug' => 'co-'.$suffix.'-'.uniqid(),
            'is_active' => true,
        ]);

        CompanyFinancialIdentity::query()->create([
            'company_id' => $company->id,
            'type' => Invoice::IDENTITY_OFFICIAL,
            'legal_name' => $company->name,
            'version' => 1,
            'is_active' => true,
        ]);

        $user = User::factory()->create(['is_active' => true]);
        $user->companies()->attach($company->id, ['is_default' => true]);

        $order = app(CompanyContext::class)->runForCompany($company->id, function () use ($company, $user, $total) {
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
                'subtotal' => $total,
                'total' => $total,
            ]);
            $quote->save();

            QuoteLine::query()->create([
                'company_id' => $company->id,
                'quote_id' => $quote->id,
                'sort_order' => 10,
                'product_type' => 'generic',
                'title' => 'Item',
                'qty' => 1,
                'unit_price' => $total,
                'line_total' => $total,
            ]);

            return Order::fromQuote($quote->fresh(['lines']));
        });

        return [$company, $order];
    }
}
