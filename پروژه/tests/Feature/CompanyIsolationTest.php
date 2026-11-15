<?php

namespace Tests\Feature;

use App\Models\Company;
use App\Models\Customer;
use App\Models\Role;
use App\Models\User;
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

        Customer::query()->create([
            'company_id' => $companyA->id,
            'type' => Customer::TYPE_ORGANIZATION,
            'name' => 'Customer A',
        ]);

        Customer::query()->create([
            'company_id' => $companyB->id,
            'type' => Customer::TYPE_ORGANIZATION,
            'name' => 'Customer B',
        ]);

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

        Customer::query()->create([
            'company_id' => $companyB->id,
            'type' => Customer::TYPE_ORGANIZATION,
            'name' => 'Private Customer B',
        ]);

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
