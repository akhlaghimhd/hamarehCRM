<?php

namespace Tests\Feature;

use App\Actions\Identity\CreateCompanyForUser;
use App\Actions\Identity\SaveCompanyBankAccount;
use App\Actions\Identity\SaveCompanyFinancialIdentity;
use App\Filament\Resources\CompanyFinancialIdentityResource;
use App\Filament\Resources\CompanyResource\Pages\ViewCompany;
use App\Filament\Resources\CompanyFinancialIdentityResource\Pages\EditCompanyFinancialIdentity;
use App\Filament\Resources\CompanyFinancialIdentityResource\RelationManagers\BankAccountsRelationManager;
use App\Models\Company;
use App\Models\CompanyBankAccount;
use App\Models\CompanyFinancialIdentity;
use App\Models\PlatformOwner;
use App\Models\Role;
use App\Models\User;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Livewire\Livewire;
use Tests\TestCase;

class CompanyFinancialIdentityTest extends TestCase
{
    use RefreshDatabase;

    private function companyAndOwner(): array
    {
        $owner = User::factory()->create();
        PlatformOwner::query()->create(['user_id' => $owner->id]);
        $company = app(CreateCompanyForUser::class)->handle([
            'name' => 'شرکت مالی',
            'slug' => 'finance-company',
            'admin_name' => 'مدیر مالی',
            'admin_username' => 'finance-admin',
            'admin_email' => 'finance-admin@example.test',
            'admin_password' => 'SecurePass123',
            'admin_password_confirmation' => 'SecurePass123',
        ], $owner);

        return [$company, $owner];
    }

    public function test_company_creation_provisions_separate_official_and_unofficial_identities(): void
    {
        [$company] = $this->companyAndOwner();
        $this->assertDatabaseHas('company_financial_identities', ['company_id' => $company->id, 'type' => 'official']);
        $this->assertDatabaseHas('company_financial_identities', ['company_id' => $company->id, 'type' => 'unofficial']);
        $this->assertSame(2, CompanyFinancialIdentity::query()->where('company_id', $company->id)->count());
    }

    public function test_financial_identity_resource_is_not_registered_in_navigation(): void
    {
        $this->assertFalse(CompanyFinancialIdentityResource::shouldRegisterNavigation());
    }

    public function test_identity_updates_create_version_history(): void
    {
        [$company, $owner] = $this->companyAndOwner();
        $identity = CompanyFinancialIdentity::query()->where('company_id', $company->id)->where('type', 'official')->firstOrFail();

        $saved = app(SaveCompanyFinancialIdentity::class)->handle($identity, $company, [
            'type' => 'official',
            'legal_name' => 'شرکت رسمی نمونه',
            'national_id' => '۱۲۳۴۵۶۷۸۹۰۱',
            'economic_code' => '۰۰۱۲۳۴۵',
            'is_active' => true,
        ], $owner);

        $this->assertSame(2, $saved->version);
        $this->assertDatabaseHas('company_financial_identity_versions', ['company_financial_identity_id' => $identity->id, 'version' => 2]);
        $version = DB::table('company_financial_identity_versions')->where('company_financial_identity_id', $identity->id)->where('version', 2)->first();
        $this->assertStringContainsString('12345678901', $version->snapshot);
    }

    public function test_bank_account_numbers_are_strings_and_only_one_active_default_exists_per_identity(): void
    {
        [$company, $owner] = $this->companyAndOwner();
        $identity = app(SaveCompanyFinancialIdentity::class)->handle(
            CompanyFinancialIdentity::query()->where('company_id', $company->id)->where('type', 'official')->firstOrFail(),
            $company,
            ['type' => 'official', 'legal_name' => 'شرکت رسمی', 'is_active' => true],
            $owner,
        );

        $service = app(SaveCompanyBankAccount::class);
        $first = $service->handle(null, $identity, [
            'account_holder' => 'شرکت رسمی', 'bank_name' => 'بانک نمونه',
            'account_number' => '۰۰۰۱۲۳۴۵', 'is_default' => true, 'is_active' => true,
        ], $owner);
        $second = $service->handle(null, $identity, [
            'account_holder' => 'شرکت رسمی', 'bank_name' => 'بانک دوم',
            'iban' => 'IR000000000000000000000000', 'is_default' => true, 'is_active' => true,
        ], $owner);

        $this->assertSame('00012345', $first->fresh()->account_number);
        $this->assertFalse($first->fresh()->is_default);
        $this->assertTrue($second->is_default);
        $this->assertSame(1, CompanyBankAccount::query()->where('company_financial_identity_id', $identity->id)->where('is_default', true)->count());
        $this->assertDatabaseHas('company_bank_account_versions', ['company_bank_account_id' => $first->id, 'version' => 2]);
    }

    public function test_owner_can_open_financial_identity_and_bank_account_management_ui(): void
    {
        [$company, $owner] = $this->companyAndOwner();
        $identity = CompanyFinancialIdentity::query()
            ->where('company_id', $company->id)
            ->where('type', 'official')
            ->firstOrFail();

        $this->actingAs($owner);

        $this->assertTrue(CompanyFinancialIdentityResource::canViewAny());
        $this->assertTrue(CompanyFinancialIdentityResource::canEdit($identity));

        $this->assertTrue(BankAccountsRelationManager::canViewForRecord($identity, EditCompanyFinancialIdentity::class));

        $page = Livewire::test(EditCompanyFinancialIdentity::class, ['record' => $identity->getRouteKey()]);
        $this->assertContains(BankAccountsRelationManager::class, $page->instance()->getRelationManagers());
        $this->assertContains(BankAccountsRelationManager::class, $page->instance()->getCachedRelationManagers());

        $page->assertOk();

        Livewire::test(BankAccountsRelationManager::class, [
            'ownerRecord' => $identity,
            'pageClass' => EditCompanyFinancialIdentity::class,
        ])
            ->assertOk()
            ->assertSee('حساب‌های دریافت')
            ->assertSee('افزودن حساب')
            ->assertSee('شماره حساب');
    }

    public function test_company_dashboard_shows_manager_and_both_financial_profiles(): void
    {
        [$company, $owner] = $this->companyAndOwner();
        $this->actingAs($owner);

        $page = Livewire::test(ViewCompany::class, ['record' => $company->getRouteKey()])
            ->assertOk()
            ->assertSee('داشبورد مدیریت شرکت')
            ->assertSee('مدیر مالی')
            ->assertSee('هویت مالی رسمی')
            ->assertSee('هویت مالی غیررسمی')
            ->assertSee('مدیریت مدیر شرکت')
            ->assertSee('هویت رسمی')
            ->assertSee('هویت غیررسمی');

        $this->assertNotNull($page->instance()->getRecord());
    }

    public function test_member_of_another_company_cannot_edit_financial_identity(): void
    {
        [$company] = $this->companyAndOwner();
        $otherCompany = Company::query()->create(['name' => 'شرکت دیگر', 'slug' => 'another-company']);
        $otherUser = User::factory()->create();
        $role = Role::query()->create(['company_id' => $otherCompany->id, 'name' => 'admin', 'label' => 'مدیر شرکت', 'is_system' => true]);
        $otherUser->companies()->attach($otherCompany->id, ['role_id' => $role->id, 'is_default' => true]);
        $identity = CompanyFinancialIdentity::query()->where('company_id', $company->id)->firstOrFail();

        $this->expectException(AuthorizationException::class);
        app(SaveCompanyFinancialIdentity::class)->handle($identity, $company, [
            'type' => $identity->type, 'legal_name' => 'تغییر غیرمجاز',
        ], $otherUser);
    }
}
