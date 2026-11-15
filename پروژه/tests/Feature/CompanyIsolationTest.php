<?php

namespace Tests\Feature;

use App\Models\Company;
use App\Models\Customer;
use App\Models\Role;
use App\Models\User;
use App\Support\CompanyContext;
use Illuminate\Foundation\Testing\RefreshDatabase;
use RuntimeException;
use Tests\TestCase;

class CompanyIsolationTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_only_sees_customers_of_the_selected_company(): void
    {
        [$companyA, $companyB] = $this->createCompanies();

        $user = User::factory()->create();
        $user->companies()->attach($companyA->id, ['is_default' => true]);
        $user->companies()->attach($companyB->id, ['is_default' => false]);

        $context = app(CompanyContext::class);

        $context->runForCompany($companyA->id, fn () => Customer::query()->create([
            'type' => Customer::TYPE_ORGANIZATION,
            'name' => 'Customer A',
        ]));

        $context->runForCompany($companyB->id, fn () => Customer::query()->create([
            'type' => Customer::TYPE_ORGANIZATION,
            'name' => 'Customer B',
        ]));

        $this->actingAs($user);
        session(['current_company_id' => $companyA->id]);

        $this->assertSame(
            ['Customer A'],
            Customer::query()->orderBy('name')->pluck('name')->all(),
        );
    }

    public function test_user_cannot_read_customers_by_setting_a_non_member_company_in_session(): void
    {
        [$companyA, $companyB] = $this->createCompanies();

        $user = User::factory()->create();
        $user->companies()->attach($companyA->id, ['is_default' => true]);

        app(CompanyContext::class)->runForCompany($companyB->id, fn () => Customer::query()->create([
            'type' => Customer::TYPE_ORGANIZATION,
            'name' => 'Private Customer B',
        ]));

        $this->actingAs($user);
        session(['current_company_id' => $companyB->id]);

        $this->assertSame([], Customer::query()->pluck('name')->all());
    }

    public function test_default_company_is_not_guessed_when_membership_has_no_default(): void
    {
        [$companyA] = $this->createCompanies();

        $user = User::factory()->create();
        $user->companies()->attach($companyA->id, ['is_default' => false]);

        $this->assertNull($user->defaultCompany());
        $this->assertNull($user->currentCompanyId());
    }

    public function test_current_role_must_belong_to_the_selected_company(): void
    {
        [$companyA, $companyB] = $this->createCompanies();

        $foreignRole = Role::query()->create([
            'company_id' => $companyB->id,
            'name' => 'admin',
            'label' => 'Company B Admin',
            'is_system' => true,
        ]);

        $user = User::factory()->create();
        $user->companies()->attach($companyA->id, [
            'role_id' => $foreignRole->id,
            'is_default' => true,
        ]);

        $this->assertNull($user->currentRole());
        $this->assertFalse($user->isCompanyAdmin());
    }

    public function test_customer_creation_requires_a_valid_company_context(): void
    {
        [$companyA] = $this->createCompanies();

        $user = User::factory()->create();
        $user->companies()->attach($companyA->id, ['is_default' => false]);

        $this->actingAs($user);

        $this->expectException(RuntimeException::class);

        Customer::query()->create([
            'type' => Customer::TYPE_ORGANIZATION,
            'name' => 'Unscoped Customer',
        ]);
    }

    public function test_inactive_company_cannot_be_selected_or_used_as_default(): void
    {
        [$activeCompany, $inactiveCompany] = $this->createCompanies();
        $inactiveCompany->update(['is_active' => false]);

        $user = User::factory()->create();
        $user->companies()->attach($activeCompany->id, ['is_default' => true]);
        $user->companies()->attach($inactiveCompany->id, ['is_default' => false]);

        $this->assertSame($activeCompany->id, $user->currentCompanyId());

        $this->actingAs($user);
        session(['current_company_id' => $inactiveCompany->id]);

        $this->assertNull($user->currentCompanyId());
        $this->assertSame([], Customer::query()->pluck('name')->all());
    }

    public function test_company_scoped_models_fail_closed_without_an_explicit_context_in_console(): void
    {
        [$companyA, $companyB] = $this->createCompanies();
        $context = app(CompanyContext::class);

        $context->runForCompany($companyA->id, fn () => Customer::query()->create([
            'type' => Customer::TYPE_ORGANIZATION,
            'name' => 'Customer A',
        ]));
        $context->runForCompany($companyB->id, fn () => Customer::query()->create([
            'type' => Customer::TYPE_ORGANIZATION,
            'name' => 'Customer B',
        ]));

        auth()->logout();
        session()->forget('current_company_id');

        $this->assertSame([], Customer::query()->pluck('name')->all());

        $names = $context->runForCompany(
            $companyA->id,
            fn () => Customer::query()->pluck('name')->all(),
        );

        $this->assertSame(['Customer A'], $names);
    }

    /**
     * @return array{Company, Company}
     */
    private function createCompanies(): array
    {
        return [
            Company::query()->create([
                'name' => 'Company A',
                'slug' => 'company-a',
                'is_active' => true,
            ]),
            Company::query()->create([
                'name' => 'Company B',
                'slug' => 'company-b',
                'is_active' => true,
            ]),
        ];
    }
}
