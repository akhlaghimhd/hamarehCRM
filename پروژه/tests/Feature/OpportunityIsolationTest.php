<?php

namespace Tests\Feature;

use App\Models\Company;
use App\Models\Customer;
use App\Models\Opportunity;
use App\Models\User;
use App\Support\CompanyContext;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class OpportunityIsolationTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_only_sees_opportunities_of_the_selected_company(): void
    {
        [$companyA, $companyB] = $this->createCompanies();
        $user = User::factory()->create(['is_active' => true]);
        $user->companies()->attach($companyA->id, ['is_default' => true]);
        $user->companies()->attach($companyB->id, ['is_default' => false]);

        $context = app(CompanyContext::class);

        $context->runForCompany($companyA->id, function () use ($companyA, $user): void {
            $customer = Customer::query()->create([
                'type' => Customer::TYPE_ORGANIZATION,
                'name' => 'Customer A',
                'is_active' => true,
            ]);

            Opportunity::query()->create([
                'company_id' => $companyA->id,
                'customer_id' => $customer->id,
                'owner_user_id' => $user->id,
                'title' => 'Opp A',
                'status' => Opportunity::STATUS_ACTIVE,
            ]);
        });

        $context->runForCompany($companyB->id, function () use ($companyB, $user): void {
            $customer = Customer::query()->create([
                'type' => Customer::TYPE_ORGANIZATION,
                'name' => 'Customer B',
                'is_active' => true,
            ]);

            Opportunity::query()->create([
                'company_id' => $companyB->id,
                'customer_id' => $customer->id,
                'owner_user_id' => $user->id,
                'title' => 'Opp B',
                'status' => Opportunity::STATUS_ACTIVE,
            ]);
        });

        $this->actingAs($user);
        session(['current_company_id' => $companyA->id]);

        $this->assertSame(
            ['Opp A'],
            Opportunity::query()->orderBy('title')->pluck('title')->all(),
        );
    }

    public function test_member_cannot_read_other_company_opportunities_via_session_spoof(): void
    {
        [$companyA, $companyB] = $this->createCompanies();
        $user = User::factory()->create(['is_active' => true]);
        $user->companies()->attach($companyA->id, ['is_default' => true]);

        app(CompanyContext::class)->runForCompany($companyB->id, function () use ($companyB): void {
            $customer = Customer::query()->create([
                'type' => Customer::TYPE_ORGANIZATION,
                'name' => 'Private B',
                'is_active' => true,
            ]);

            Opportunity::query()->create([
                'company_id' => $companyB->id,
                'customer_id' => $customer->id,
                'title' => 'Secret Opp B',
                'status' => Opportunity::STATUS_ACTIVE,
            ]);
        });

        $this->actingAs($user);
        session(['current_company_id' => $companyB->id]);

        $this->assertSame([], Opportunity::query()->pluck('title')->all());
    }

    /**
     * @return array{Company, Company}
     */
    private function createCompanies(): array
    {
        return [
            Company::query()->create([
                'name' => 'Company A',
                'slug' => 'company-a-'.uniqid(),
                'is_active' => true,
            ]),
            Company::query()->create([
                'name' => 'Company B',
                'slug' => 'company-b-'.uniqid(),
                'is_active' => true,
            ]),
        ];
    }
}
