<?php

namespace Tests\Feature;

use App\Filament\Pages\CompanySettings as CompanySettingsPage;
use App\Models\Company;
use App\Models\Role;
use App\Models\User;
use App\Support\CompanyTheme;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class CompanySettingsPageTest extends TestCase
{
    use RefreshDatabase;

    public function test_company_admin_can_configure_theme_from_company_settings_page(): void
    {
        [$company, $user] = $this->companyWithAdmin();

        $this->actingAs($user);
        session(['current_company_id' => $company->id]);

        Livewire::test(CompanySettingsPage::class)
            ->assertSee('پوسته و هویت بصری شرکت')
            ->set('themePalette', 'ocean')
            ->assertSet('themePrimary', '#0F766E')
            ->set('themeDensity', 'compact')
            ->call('saveTheme');

        $theme = app(CompanyTheme::class)->current();

        $this->assertSame('ocean', $theme['palette']);
        $this->assertSame('#0F766E', $theme['primary']);
        $this->assertSame('compact', $theme['density']);
    }

    public function test_reserved_theme_key_cannot_be_managed_as_a_generic_setting(): void
    {
        [$company, $user] = $this->companyWithAdmin();

        $this->actingAs($user);
        session(['current_company_id' => $company->id]);

        Livewire::test(CompanySettingsPage::class)
            ->set('key', 'ui.theme')
            ->set('type', 'json')
            ->set('value', '{}')
            ->call('saveSetting')
            ->assertHasErrors(['key']);

        $this->assertDatabaseMissing('company_settings', [
            'company_id' => $company->id,
            'key' => 'ui.theme',
        ]);
    }

    private function companyWithAdmin(): array
    {
        $company = Company::query()->create([
            'name' => 'شرکت تنظیمات',
            'slug' => 'company-settings-page',
            'is_active' => true,
        ]);
        $role = Role::query()->create([
            'company_id' => $company->id,
            'name' => 'admin',
            'label' => 'مدیر شرکت',
        ]);
        $user = User::factory()->create();
        $user->companies()->attach($company->id, [
            'role_id' => $role->id,
            'is_default' => true,
        ]);

        return [$company, $user];
    }
}
