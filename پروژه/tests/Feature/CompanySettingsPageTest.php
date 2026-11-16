<?php

namespace Tests\Feature;

use App\Filament\Pages\CompanySettings as CompanySettingsPage;
use App\Models\Company;
use App\Models\CompanySetting;
use App\Models\Role;
use App\Models\User;
use App\Support\CompanyTheme;
use App\Support\CompanySettings;
use Illuminate\Database\Eloquent\ModelNotFoundException;
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


    public function test_company_settings_page_cannot_read_or_delete_another_company_setting_by_id(): void
    {
        $companyA = Company::query()->create([
            'name' => 'شرکت الف',
            'slug' => 'settings-company-a',
            'is_active' => true,
        ]);
        $companyB = Company::query()->create([
            'name' => 'شرکت ب',
            'slug' => 'settings-company-b',
            'is_active' => true,
        ]);

        $roleA = Role::query()->create([
            'company_id' => $companyA->id,
            'name' => 'admin',
            'label' => 'مدیر شرکت',
        ]);
        $roleB = Role::query()->create([
            'company_id' => $companyB->id,
            'name' => 'admin',
            'label' => 'مدیر شرکت',
        ]);

        $user = User::factory()->create();
        $user->companies()->attach($companyA->id, [
            'role_id' => $roleA->id,
            'is_default' => true,
        ]);
        $user->companies()->attach($companyB->id, [
            'role_id' => $roleB->id,
            'is_default' => false,
        ]);

        $this->actingAs($user);
        session(['current_company_id' => $companyA->id]);

        $setting = app(CompanySettings::class)->set(
            $companyA->id,
            'private.company_a',
            'string',
            'secret-a',
        );

        session(['current_company_id' => $companyB->id]);

        $this->assertDatabaseHas('company_settings', [
            'id' => $setting->id,
            'company_id' => $companyA->id,
            'key' => 'private.company_a',
        ]);

        $this->assertNull(CompanySetting::query()->find($setting->id));

        try {
            Livewire::test(CompanySettingsPage::class)->call('editSetting', $setting->id);
            $this->fail('A company admin must not read a setting from another company.');
        } catch (ModelNotFoundException) {
            // The company global scope must make foreign settings indistinguishable from missing settings.
        }

        try {
            Livewire::test(CompanySettingsPage::class)->call('deleteSetting', $setting->id);
            $this->fail('A company admin must not delete a setting from another company.');
        } catch (ModelNotFoundException) {
            // The company global scope must prevent ID-based cross-company deletion.
        }

        $this->assertDatabaseHas('company_settings', [
            'id' => $setting->id,
            'company_id' => $companyA->id,
            'key' => 'private.company_a',
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
