<?php

namespace Tests\Feature;

use App\Models\Company;
use App\Models\CompanySetting;
use App\Models\Role;
use App\Models\User;
use App\Filament\Pages\CompanySettings as CompanySettingsPage;
use App\Support\CompanySettings;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Crypt;
use RuntimeException;
use Tests\TestCase;

class CompanySettingsTest extends TestCase
{
    use RefreshDatabase;

    public function test_settings_are_isolated_by_company_context(): void
    {
        [$companyA, $companyB, $user] = $this->fixture();
        $this->actingAs($user);
        session(['current_company_id' => $companyA->id]);

        $settings = app(CompanySettings::class);
        $settings->set($companyA->id, 'sales.default_currency', 'string', 'IRR');

        $this->assertSame('IRR', $settings->get($companyA->id, 'sales.default_currency'));
        session(['current_company_id' => $companyB->id]);
        $this->assertNull($settings->get($companyB->id, 'sales.default_currency'));
    }

    public function test_sensitive_settings_are_encrypted_at_rest_and_decrypted_on_read(): void
    {
        [$company, , $user] = $this->fixture();
        $this->actingAs($user);
        session(['current_company_id' => $company->id]);

        $settings = app(CompanySettings::class);
        $settings->set($company->id, 'integration.api_token', 'string', 'secret-token', [], true);

        $stored = CompanySetting::query()->where('key', 'integration.api_token')->first();

        $this->assertNotSame('secret-token', $stored->value);
        $this->assertSame('secret-token', json_decode(Crypt::decryptString($stored->value), true));
        $this->assertSame('secret-token', $settings->get($company->id, 'integration.api_token'));
    }

    public function test_sensitive_values_are_redacted_from_audit_events(): void
    {
        [$company, , $user] = $this->fixture();
        $this->actingAs($user);
        session(['current_company_id' => $company->id]);

        app(CompanySettings::class)->set($company->id, 'integration.api_token', 'string', 'secret-token', [], true);

        $event = $company->settings()->first()->newQuery()
            ->getConnection()
            ->table('audit_events')
            ->where('auditable_type', CompanySetting::class)
            ->first();

        $this->assertNotNull($event);
        $this->assertStringNotContainsString('secret-token', json_encode($event));
        $this->assertSame('[REDACTED]', json_decode($event->new_values, true)['value']);
    }

    public function test_setting_values_are_validated_against_declared_type_and_rules(): void
    {
        [$company, , $user] = $this->fixture();
        $this->actingAs($user);
        session(['current_company_id' => $company->id]);

        $this->expectException(\Illuminate\Validation\ValidationException::class);

        app(CompanySettings::class)->set($company->id, 'sales.credit_days', 'integer', 'many', ['min:0']);
    }

    public function test_cannot_read_or_write_settings_outside_current_company_context(): void
    {
        [$companyA, $companyB, $user] = $this->fixture();
        $this->actingAs($user);
        session(['current_company_id' => $companyA->id]);

        $this->expectException(RuntimeException::class);

        app(CompanySettings::class)->set($companyB->id, 'sales.default_currency', 'string', 'IRR');
    }

    public function test_non_admin_cannot_manage_company_settings(): void
    {
        [$company] = $this->fixture();
        $staff = User::factory()->create();
        $staff->companies()->attach($company->id, ['is_default' => true]);

        $this->actingAs($staff);
        session(['current_company_id' => $company->id]);

        $this->expectException(\Illuminate\Auth\Access\AuthorizationException::class);

        app(CompanySettings::class)->set($company->id, 'sales.default_currency', 'string', 'IRR');
    }

    public function test_only_active_company_admin_can_access_settings_page(): void
    {
        [$company, , $admin] = $this->fixture();
        $this->actingAs($admin);
        session(['current_company_id' => $company->id]);

        $this->assertTrue(CompanySettingsPage::canAccess());

        $staff = User::factory()->create();
        $staff->companies()->attach($company->id, ['is_default' => true]);
        $this->actingAs($staff);

        $this->assertFalse(CompanySettingsPage::canAccess());
    }

    private function fixture(): array
    {
        $companyA = Company::query()->create(['name' => 'Settings A', 'slug' => 'settings-a', 'is_active' => true]);
        $companyB = Company::query()->create(['name' => 'Settings B', 'slug' => 'settings-b', 'is_active' => true]);
        $user = User::factory()->create();
        $adminRole = Role::query()->create(['company_id' => $companyA->id, 'name' => 'admin', 'label' => 'Admin']);
        $user->companies()->attach($companyA->id, ['role_id' => $adminRole->id, 'is_default' => true]);
        $user->companies()->attach($companyB->id, ['is_default' => false]);

        return [$companyA, $companyB, $user];
    }
}
