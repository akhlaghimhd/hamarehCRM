<?php

namespace Tests\Feature;

use App\Filament\Pages\SwitchCompany;
use Filament\Pages\Dashboard;
use App\Models\Company;
use App\Models\Permission;
use App\Models\User;
use App\Support\CompanyContext;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class SwitchCompanyTest extends TestCase
{
    use RefreshDatabase;


    public function test_switching_company_replaces_permission_context_without_stale_permissions(): void
    {
        $companyA = $this->createCompany('permission-context-a');
        $companyB = $this->createCompany('permission-context-b');

        $permissionA = Permission::query()->create([
            'name' => 'leads.view',
            'label' => 'مشاهده سرنخ‌ها',
            'group' => 'crm',
        ]);
        $permissionB = Permission::query()->create([
            'name' => 'inventory.view',
            'label' => 'مشاهده موجودی',
            'group' => 'inventory',
        ]);

        $roleA = Role::query()->create([
            'company_id' => $companyA->id,
            'name' => 'sales',
            'label' => 'فروش',
            'is_system' => false,
        ]);
        $roleA->permissions()->attach($permissionA);

        $roleB = Role::query()->create([
            'company_id' => $companyB->id,
            'name' => 'warehouse',
            'label' => 'انبار',
            'is_system' => false,
        ]);
        $roleB->permissions()->attach($permissionB);

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

        $this->assertTrue($user->hasPermission('leads.view'));
        $this->assertFalse($user->hasPermission('inventory.view'));

        Livewire::test(SwitchCompany::class)
            ->set('company_id', $companyB->id)
            ->call('switch')
            ->assertRedirect(Dashboard::getUrl());

        $this->assertSame($companyB->id, $user->currentCompanyId());
        $this->assertFalse($user->hasPermission('leads.view'));
        $this->assertTrue($user->hasPermission('inventory.view'));
    }

    public function test_user_can_recover_company_context_when_selected_company_becomes_inactive(): void
    {
        $selectedCompany = $this->createCompany('selected-company');
        $availableCompany = $this->createCompany('available-company');

        $user = User::factory()->create();
        $user->companies()->attach($selectedCompany->id, ['is_default' => true]);
        $user->companies()->attach($availableCompany->id, ['is_default' => false]);

        $this->actingAs($user);
        session(['current_company_id' => $selectedCompany->id]);

        $this->assertSame($selectedCompany->id, app(CompanyContext::class)->currentCompanyId($user));

        $selectedCompany->update(['is_active' => false]);

        $this->assertNull(app(CompanyContext::class)->currentCompanyId($user));
        $this->assertTrue(SwitchCompany::canAccess());

        Livewire::test(SwitchCompany::class)
            ->assertSet('company_id', null)
            ->assertSee('انتخاب فضای کاری');

        app(CompanyContext::class)->selectForUser($user, $availableCompany->id);

        $this->assertSame($availableCompany->id, app(CompanyContext::class)->currentCompanyId($user));
    }

    public function test_switcher_is_not_available_when_the_only_active_company_is_already_selected(): void
    {
        $company = $this->createCompany('only-company');
        $user = User::factory()->create();
        $user->companies()->attach($company->id, ['is_default' => true]);

        $this->actingAs($user);
        session(['current_company_id' => $company->id]);

        $this->assertFalse(SwitchCompany::canAccess());
    }

    private function createCompany(string $slug): Company
    {
        return Company::query()->create([
            'name' => str($slug)->replace('-', ' ')->title()->toString(),
            'slug' => $slug,
            'is_active' => true,
        ]);
    }
}
