<?php

namespace Tests\Feature;

use App\Jobs\CompanyAwareJob;
use App\Jobs\Middleware\ResolveCompanyContext;
use App\Models\Company;
use App\Support\CompanyContext;
use Illuminate\Foundation\Testing\RefreshDatabase;
use RuntimeException;
use Tests\TestCase;

class CompanyAwareJobTest extends TestCase
{
    use RefreshDatabase;

    public function test_job_resolves_its_explicit_company_without_using_session_selection(): void
    {
        $companyA = $this->createCompany('Job Company A');
        $companyB = $this->createCompany('Job Company B');
        session(['current_company_id' => $companyB->id]);

        $job = new TestCompanyAwareJob($companyA->id);
        $seenCompanyId = null;

        $result = (new ResolveCompanyContext)->handle($job, function () use (&$seenCompanyId): string {
            $seenCompanyId = app(CompanyContext::class)->requireCompanyId();

            return 'handled';
        });

        $this->assertSame('handled', $result);
        $this->assertSame($companyA->id, $seenCompanyId);
        $this->assertSame($companyB->id, session('current_company_id'));
        $this->assertSame($companyB->id, app(CompanyContext::class)->currentCompanyId());
    }

    public function test_job_context_is_restored_after_job_throws(): void
    {
        $company = $this->createCompany();
        $job = new TestCompanyAwareJob($company->id);

        try {
            (new ResolveCompanyContext)->handle($job, function (): never {
                $this->assertNotNull(app(CompanyContext::class)->currentCompanyId());

                throw new RuntimeException('job failed');
            });
            $this->fail('Expected the job exception to bubble up.');
        } catch (RuntimeException $exception) {
            $this->assertSame('job failed', $exception->getMessage());
        }

        $this->assertNull(app(CompanyContext::class)->currentCompanyId());
    }

    public function test_job_for_inactive_or_missing_company_is_rejected(): void
    {
        $company = $this->createCompany();
        $company->update(['is_active' => false]);
        $job = new TestCompanyAwareJob($company->id);

        $this->expectException(RuntimeException::class);

        (new ResolveCompanyContext)->handle($job, fn () => $this->fail('Inactive company job must not run.'));
    }

    public function test_job_defaults_to_after_commit_with_bounded_retry_policy(): void
    {
        $job = new TestCompanyAwareJob($this->createCompany()->id);

        $this->assertTrue($job->afterCommit);
        $this->assertSame(3, $job->tries);
        $this->assertSame([10, 30, 60], $job->backoff);
        $this->assertSame(120, $job->timeout);
        $this->assertCount(1, $job->middleware());
    }

    private function createCompany(string $name = 'Queue Company'): Company
    {
        return Company::query()->create([
            'name' => $name,
            'slug' => str($name)->slug().'-'.uniqid(),
            'is_active' => true,
        ]);
    }
}

class TestCompanyAwareJob extends CompanyAwareJob
{
    public function handle(): void
    {
        // Test-only job; middleware is exercised directly.
    }
}
