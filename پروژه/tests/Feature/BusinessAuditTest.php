<?php

namespace Tests\Feature;

use App\Models\AuditEvent;
use App\Models\Company;
use App\Models\Customer;
use App\Models\Order;
use App\Models\Quote;
use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class BusinessAuditTest extends TestCase
{
    use RefreshDatabase;

    public function test_quote_and_order_lifecycle_fields_are_audited_without_free_form_notes(): void
    {
        $company = Company::query()->create([
            'name' => 'Business Audit Co',
            'slug' => 'business-audit-co',
            'is_active' => true,
        ]);
        $role = Role::query()->create([
            'company_id' => $company->id,
            'name' => 'admin',
            'label' => 'Admin',
            'is_system' => true,
        ]);
        $user = User::factory()->create();
        $user->companies()->attach($company->id, [
            'role_id' => $role->id,
            'is_default' => true,
        ]);

        $this->actingAs($user);
        session(['current_company_id' => $company->id]);

        $customer = Customer::query()->create([
            'type' => Customer::TYPE_ORGANIZATION,
            'name' => 'Audit Customer',
        ]);

        $quote = Quote::query()->create([
            'customer_id' => $customer->id,
            'title' => 'Initial quote',
            'status' => Quote::STATUS_DRAFT,
            'total' => 1000,
            'notes' => 'Private free-form note',
        ]);
        $quote->update([
            'status' => Quote::STATUS_SENT,
            'total' => 1200,
            'notes' => 'Changed private note',
        ]);

        $order = Order::query()->create([
            'customer_id' => $customer->id,
            'title' => 'Initial order',
            'status' => Order::STATUS_DRAFT,
            'total' => 1200,
            'notes' => 'Order private note',
        ]);
        $order->update([
            'status' => Order::STATUS_CONFIRMED,
            'confirmed_at' => now(),
            'total' => 1300,
            'notes' => 'Changed order private note',
        ]);

        $quoteEvent = AuditEvent::query()
            ->where('auditable_type', $quote->getMorphClass())
            ->where('auditable_id', $quote->id)
            ->where('event', 'updated')
            ->sole();

        $this->assertSame(Quote::STATUS_DRAFT, $quoteEvent->old_values['status']);
        $this->assertSame(Quote::STATUS_SENT, $quoteEvent->new_values['status']);
        $this->assertSame('1000', (string) $quoteEvent->old_values['total']);
        $this->assertSame('1200', (string) $quoteEvent->new_values['total']);
        $this->assertArrayNotHasKey('notes', $quoteEvent->old_values);
        $this->assertArrayNotHasKey('notes', $quoteEvent->new_values);

        $orderEvent = AuditEvent::query()
            ->where('auditable_type', $order->getMorphClass())
            ->where('auditable_id', $order->id)
            ->where('event', 'updated')
            ->sole();

        $this->assertSame(Order::STATUS_DRAFT, $orderEvent->old_values['status']);
        $this->assertSame(Order::STATUS_CONFIRMED, $orderEvent->new_values['status']);
        $this->assertSame('1200', (string) $orderEvent->old_values['total']);
        $this->assertSame('1300', (string) $orderEvent->new_values['total']);
        $this->assertArrayNotHasKey('notes', $orderEvent->old_values);
        $this->assertArrayNotHasKey('notes', $orderEvent->new_values);
        $this->assertSame($user->id, $orderEvent->actor_user_id);
    }
}
