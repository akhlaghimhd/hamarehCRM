<?php

namespace Tests\Feature;

use App\Models\Company;
use App\Support\CompanyContext;
use App\Support\NumberSequence;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use RuntimeException;
use Tests\TestCase;

class NumberSequenceTest extends TestCase
{
    use RefreshDatabase;

    public function test_numbers_increase_atomically_within_a_company_and_sequence(): void
    {
        $company = $this->createCompany();

        $numbers = app(CompanyContext::class)->runForCompany(
            $company->id,
            fn () => [
                app(NumberSequence::class)->next($company->id, 'quote', 'Q-2026-'),
                app(NumberSequence::class)->next($company->id, 'quote', 'Q-2026-'),
                app(NumberSequence::class)->next($company->id, 'quote', 'Q-2026-'),
            ],
        );

        $this->assertSame(['Q-2026-0001', 'Q-2026-0002', 'Q-2026-0003'], $numbers);
    }

    public function test_each_company_and_document_type_has_an_independent_sequence(): void
    {
        $companyA = $this->createCompany('Company A');
        $companyB = $this->createCompany('Company B');
        $context = app(CompanyContext::class);

        $numberA = $context->runForCompany(
            $companyA->id,
            fn () => app(NumberSequence::class)->next($companyA->id, 'quote', 'Q-2026-'),
        );
        $numberB = $context->runForCompany(
            $companyB->id,
            fn () => app(NumberSequence::class)->next($companyB->id, 'quote', 'Q-2026-'),
        );
        $orderNumber = $context->runForCompany(
            $companyA->id,
            fn () => app(NumberSequence::class)->next($companyA->id, 'order', 'O-2026-'),
        );

        $this->assertSame('Q-2026-0001', $numberA);
        $this->assertSame('Q-2026-0001', $numberB);
        $this->assertSame('O-2026-0001', $orderNumber);
    }

    public function test_sequence_increment_rolls_back_with_its_database_transaction(): void
    {
        $company = $this->createCompany();
        $context = app(CompanyContext::class);

        try {
            $context->runForCompany($company->id, function () use ($company): void {
                DB::transaction(function () use ($company): void {
                    app(NumberSequence::class)->next($company->id, 'quote', 'Q-2026-');

                    throw new RuntimeException('rollback');
                });
            });
        } catch (RuntimeException $exception) {
            $this->assertSame('rollback', $exception->getMessage());
        }

        $next = $context->runForCompany(
            $company->id,
            fn () => app(NumberSequence::class)->next($company->id, 'quote', 'Q-2026-'),
        );

        $this->assertSame('Q-2026-0001', $next);
    }

    public function test_sequence_cannot_be_issued_for_another_company_context(): void
    {
        $companyA = $this->createCompany();
        $companyB = $this->createCompany('Company B');

        $this->expectException(RuntimeException::class);

        app(CompanyContext::class)->runForCompany(
            $companyA->id,
            fn () => app(NumberSequence::class)->next($companyB->id, 'quote', 'Q-2026-'),
        );
    }

    private function createCompany(string $name = 'Sequence Company'): Company
    {
        return Company::query()->create([
            'name' => $name,
            'slug' => str($name)->slug().'-'.uniqid(),
            'is_active' => true,
        ]);
    }
}
