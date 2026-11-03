<?php

namespace Tests\Feature;

use App\Models\Company;
use App\Models\Customer;
use App\Models\User;
use App\Support\CurrentCompany;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class CompanyIsolationTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function customer_queries_do_not_leak_across_companies(): void
    {
        $companyA = Company::query()->create(['name' => 'A', 'code' => 'A']);
        $companyB = Company::query()->create(['name' => 'B', 'code' => 'B']);

        CurrentCompany::set($companyA);
        Customer::query()->create([
            'company_id' => $companyA->id,
            'type' => 'organization',
            'display_name' => 'Customer A',
            'status' => 'active',
        ]);

        CurrentCompany::set($companyB);
        Customer::query()->create([
            'company_id' => $companyB->id,
            'type' => 'organization',
            'display_name' => 'Customer B',
            'status' => 'active',
        ]);

        CurrentCompany::set($companyA);
        $names = Customer::query()->pluck('display_name')->all();

        $this->assertSame(['Customer A'], $names);
    }
}
