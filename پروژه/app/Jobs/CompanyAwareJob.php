<?php

namespace App\Jobs;

use App\Jobs\Middleware\ResolveCompanyContext;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use InvalidArgumentException;

/**
 * Base contract for queued work that reads or changes company-owned data.
 *
 * Subclasses should make externally visible effects idempotent and may
 * override tries, backoff, timeout, and failed() for their own workload.
 */
abstract class CompanyAwareJob implements ShouldQueue
{
    use Dispatchable;
    use InteractsWithQueue;
    use Queueable;
    use SerializesModels;

    public int $tries = 3;

    public array $backoff = [10, 30, 60];

    public int $timeout = 120;

    public function __construct(public readonly int $companyId)
    {
        if ($companyId < 1) {
            throw new InvalidArgumentException('شناسه شرکت برای اجرای Job الزامی است.');
        }

        $this->afterCommit = true;
    }

    public function middleware(): array
    {
        return [new ResolveCompanyContext];
    }
}
