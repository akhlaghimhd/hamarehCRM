<?php

namespace Tests\Feature;

use App\Filament\Resources\AuditEventResource;
use App\Models\AuditEvent;
use App\Models\Company;
use App\Models\Role;
use App\Models\User;
use App\Support\CompanyContext;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AuditEventResourceTest extends TestCase
{
    use RefreshDatabase;

    public function test_only_active_company_admin_can_view_audit_events(): void
    {
        [$company, $admin] = $this->fixture();

        $this->actingAs($admin);
        session(['current_company_id' => $company->id]);

        $this->assertTrue(AuditEventResource::canViewAny());

        $staff = User::factory()->create();
        $staff->companies()->attach($company->id, ['is_default' => true]);

        $this->actingAs($staff);

        $this->assertFalse(AuditEventResource::canViewAny());
    }

    public function test_audit_event_resource_is_read_only(): void
    {
        $this->assertFalse(AuditEventResource::canCreate());
        $this->assertFalse(AuditEventResource::canEdit(new AuditEvent()));
        $this->assertFalse(AuditEventResource::canDelete(new AuditEvent()));
    }

    public function test_audit_event_list_remains_scoped_to_the_selected_company(): void
    {
        [$companyA, $admin] = $this->fixture();
        $companyB = Company::query()->create([
            'name' => 'Audit Company B',
            'slug' => 'audit-company-b',
            'is_active' => true,
        ]);

        app(CompanyContext::class)->runForCompany($companyA->id, fn () => AuditEvent::query()->create([
            'auditable_type' => User::class,
            'auditable_id' => $admin->id,
            'actor_user_id' => $admin->id,
            'event' => 'created',
            'new_values' => ['name' => 'A'],
            'occurred_at' => now(),
        ]));

        app(CompanyContext::class)->runForCompany($companyB->id, fn () => AuditEvent::query()->create([
            'auditable_type' => User::class,
            'auditable_id' => $admin->id,
            'actor_user_id' => $admin->id,
            'event' => 'created',
            'new_values' => ['name' => 'B'],
            'occurred_at' => now(),
        ]));

        $this->actingAs($admin);
        session(['current_company_id' => $companyA->id]);

        $events = AuditEventResource::getEloquentQuery()->get();

        $this->assertCount(1, $events);
        $this->assertSame($companyA->id, $events->sole()->company_id);
        $this->assertSame(['name' => 'A'], $events->sole()->new_values);
    }

    private function fixture(): array
    {
        $company = Company::query()->create([
            'name' => 'Audit Company A',
            'slug' => 'audit-company-a',
            'is_active' => true,
        ]);
        $role = Role::query()->create([
            'company_id' => $company->id,
            'name' => 'admin',
            'label' => 'Admin',
            'is_system' => true,
        ]);
        $admin = User::factory()->create();
        $admin->companies()->attach($company->id, [
            'role_id' => $role->id,
            'is_default' => true,
        ]);

        return [$company, $admin];
    }
}
