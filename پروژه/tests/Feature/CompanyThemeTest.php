<?php

namespace Tests\Feature;

use App\Models\Company;
use App\Models\Role;
use App\Models\User;
use App\Support\CompanySettings;
use App\Support\CompanyTheme;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

class CompanyThemeTest extends TestCase
{
    use RefreshDatabase;

    public function test_theme_settings_are_isolated_by_company(): void
    {
        [$companyA, $companyB, $user] = $this->adminInTwoCompanies();

        $this->actingAs($user);
        session(['current_company_id' => $companyA->id]);

        $theme = app(CompanyTheme::class);
        $theme->save([
            'palette' => 'custom',
            'primary' => '#123456',
            'secondary' => '#654321',
            'density' => 'compact',
        ]);

        $this->assertSame('#123456', $theme->current()['primary']);
        $this->assertSame('#654321', $theme->current()['secondary']);
        $this->assertSame('compact', $theme->current()['density']);

        session(['current_company_id' => $companyB->id]);

        $this->assertSame('default', $theme->current()['palette']);
        $this->assertSame('#087E78', $theme->current()['primary']);
        $this->assertSame('balanced', $theme->current()['density']);
    }

    public function test_preset_palette_and_density_are_saved_for_the_company(): void
    {
        [$company, , $user] = $this->adminInTwoCompanies();

        $this->actingAs($user);
        session(['current_company_id' => $company->id]);

        app(CompanyTheme::class)->save([
            'palette' => 'ocean',
            'primary' => '#0F766E',
            'secondary' => '#2563EB',
            'density' => 'comfortable',
        ]);

        $theme = app(CompanyTheme::class)->current();

        $this->assertSame('ocean', $theme['palette']);
        $this->assertSame('#0F766E', $theme['primary']);
        $this->assertSame('#2563EB', $theme['secondary']);
        $this->assertSame('comfortable', $theme['density']);
    }

    public function test_invalid_theme_colors_are_rejected_server_side(): void
    {
        [$company, , $user] = $this->adminInTwoCompanies();

        $this->actingAs($user);
        session(['current_company_id' => $company->id]);

        $this->expectException(ValidationException::class);

        app(CompanyTheme::class)->save([
            'palette' => 'custom',
            'primary' => 'red; background:url(javascript:alert(1))',
            'secondary' => '#654321',
            'density' => 'balanced',
        ]);
    }

    public function test_non_admin_cannot_change_company_theme(): void
    {
        $company = $this->createCompany('staff-company');
        $staffRole = Role::query()->create([
            'company_id' => $company->id,
            'name' => 'staff',
            'label' => 'کارمند',
            'is_system' => false,
        ]);
        $user = User::factory()->create();
        $user->companies()->attach($company->id, [
            'role_id' => $staffRole->id,
            'is_default' => true,
        ]);

        $this->actingAs($user);
        session(['current_company_id' => $company->id]);

        $this->expectException(AuthorizationException::class);

        app(CompanyTheme::class)->save([
            'palette' => 'default',
            'primary' => '#087E78',
            'secondary' => '#3976B8',
            'density' => 'balanced',
        ]);
    }



    public function test_reserved_theme_setting_cannot_be_deleted_by_generic_settings_service(): void
    {
        [$company, , $user] = $this->adminInTwoCompanies();

        $this->actingAs($user);
        session(['current_company_id' => $company->id]);

        app(CompanyTheme::class)->save([
            'palette' => 'default',
            'primary' => '#087E78',
            'secondary' => '#3976B8',
            'density' => 'balanced',
        ]);

        $this->expectException(InvalidArgumentException::class);
        app(CompanySettings::class)->forget($company->id, 'ui.theme');
    }


    public function test_generic_settings_service_cannot_overwrite_reserved_theme_key(): void
    {
        [$company, , $user] = $this->adminInTwoCompanies();

        $this->actingAs($user);
        session(['current_company_id' => $company->id]);

        $this->expectException(InvalidArgumentException::class);
        app(CompanySettings::class)->set(
            $company->id,
            'ui.theme',
            'json',
            ['palette' => 'custom', 'primary' => '#000000'],
        );
    }


    public function test_non_admin_gets_safe_default_when_legacy_theme_setting_is_sensitive(): void
    {
        $company = $this->createCompany('legacy-sensitive-theme');
        $staffRole = Role::query()->create([
            'company_id' => $company->id,
            'name' => 'staff',
            'label' => 'کارمند',
            'is_system' => false,
        ]);
        $user = User::factory()->create();
        $user->companies()->attach($company->id, [
            'role_id' => $staffRole->id,
            'is_default' => true,
        ]);

        DB::table('company_settings')->insert([
            'company_id' => $company->id,
            'key' => 'ui.theme',
            'type' => 'json',
            'value' => Crypt::encryptString(json_encode([
                'palette' => 'custom',
                'primary' => '#123456',
                'secondary' => '#654321',
                'density' => 'compact',
            ], JSON_THROW_ON_ERROR)),
            'validation_rules' => null,
            'is_sensitive' => true,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $this->actingAs($user);
        session(['current_company_id' => $company->id]);

        $theme = app(CompanyTheme::class)->current();

        $this->assertSame('default', $theme['palette']);
        $this->assertSame('#087E78', $theme['primary']);
        $this->assertSame('balanced', $theme['density']);
    }

    /**
     * @return array{Company, Company, User}
     */
    private function adminInTwoCompanies(): array
    {
        $companyA = $this->createCompany('theme-company-a');
        $companyB = $this->createCompany('theme-company-b');

        $roleA = $this->createAdminRole($companyA);
        $roleB = $this->createAdminRole($companyB);

        $user = User::factory()->create();
        $user->companies()->attach($companyA->id, [
            'role_id' => $roleA->id,
            'is_default' => true,
        ]);
        $user->companies()->attach($companyB->id, [
            'role_id' => $roleB->id,
            'is_default' => false,
        ]);

        return [$companyA, $companyB, $user];
    }

    private function createCompany(string $slug): Company
    {
        return Company::query()->create([
            'name' => str($slug)->replace('-', ' ')->title()->toString(),
            'slug' => $slug,
            'is_active' => true,
        ]);
    }

    private function createAdminRole(Company $company): Role
    {
        return Role::query()->create([
            'company_id' => $company->id,
            'name' => 'admin',
            'label' => 'مدیر شرکت',
            'is_system' => true,
            'inherit_permissions' => false,
        ]);
    }
}
