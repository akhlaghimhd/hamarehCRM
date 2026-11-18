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
use InvalidArgumentException;
use Tests\TestCase;

class InvoicePaymentTest extends TestCase
{
    use RefreshDatabase;

    public function test_invoice_is_issued_from_order_with_snapshot(): void
    {
        [$company, $order] = $this->makeOrderWithIdentity();

        $invoice = app(CompanyContext::class)->runForCompany(
            $company->id,
            fn () => Invoice::fromOrder($order, Invoice::IDENTITY_OFFICIAL),
        );

        $this->assertSame($order->id, $invoice->order_id);
        $this->assertSame((float) $order->total, (float) $invoice->total);
        $this->assertSame(Invoice::STATUS_ISSUED, $invoice->status);
        $this->assertSame(Invoice::IDENTITY_OFFICIAL, $invoice->identity_type);
        $this->assertNotEmpty($invoice->issuer_snapshot['legal_name'] ?? null);
        $this->assertSame(0.0, (float) $invoice->paid_amount);
    }

    public function test_partial_payment_updates_balance_and_status(): void
    {
        [$company, $order] = $this->makeOrderWithIdentity(total: 1000);

        $context = app(CompanyContext::class);

        $invoice = $context->runForCompany(
            $company->id,
            fn () => Invoice::fromOrder($order, Invoice::IDENTITY_UNOFFICIAL),
        );

        $context->runForCompany($company->id, function () use ($invoice) {
            Payment::recordForInvoice($invoice, ['amount' => 400, 'paid_on' => now()->toDateString()]);
        });

        $invoice->refresh();
        $this->assertSame(Invoice::STATUS_PARTIAL, $invoice->status);
        $this->assertSame(400.0, (float) $invoice->paid_amount);
        $this->assertSame(600.0, $invoice->balance());

        $context->runForCompany($company->id, function () use ($invoice) {
            Payment::recordForInvoice($invoice, ['amount' => 600, 'paid_on' => now()->toDateString()]);
        });

        $invoice->refresh();
        $this->assertSame(Invoice::STATUS_PAID, $invoice->status);
        $this->assertSame(0.0, $invoice->balance());
    }

    public function test_payment_cannot_exceed_balance(): void
    {
        [$company, $order] = $this->makeOrderWithIdentity(total: 500);

        $invoice = app(CompanyContext::class)->runForCompany(
            $company->id,
            fn () => Invoice::fromOrder($order, Invoice::IDENTITY_OFFICIAL),
        );

        $this->expectException(InvalidArgumentException::class);

        app(CompanyContext::class)->runForCompany(
            $company->id,
            fn () => Payment::recordForInvoice($invoice, ['amount' => 501]),
        );
    }

    /**
     * @return array{Company, Order}
     */
    private function makeOrderWithIdentity(float $total = 1000): array
    {
        $company = Company::query()->create([
            'name' => 'Finance Co',
            'slug' => 'finance-co-'.uniqid(),
            'is_active' => true,
        ]);

        foreach ([Invoice::IDENTITY_OFFICIAL, Invoice::IDENTITY_UNOFFICIAL] as $type) {
            CompanyFinancialIdentity::query()->create([
                'company_id' => $company->id,
                'type' => $type,
                'legal_name' => $company->name.' '.$type,
                'version' => 1,
                'is_active' => true,
            ]);
        }

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
