<?php

namespace App\Jobs\Middleware;

use App\Jobs\CompanyAwareJob;
use App\Support\CompanyContext;
use Closure;
use InvalidArgumentException;

class ResolveCompanyContext
{
    public function handle(CompanyAwareJob $job, Closure $next): mixed
    {
        if ($job->companyId < 1) {
            throw new InvalidArgumentException('Job فاقد شناسه شرکت معتبر است.');
        }

        return app(CompanyContext::class)->runForCompany(
            $job->companyId,
            fn () => $next($job),
        );
    }
}
