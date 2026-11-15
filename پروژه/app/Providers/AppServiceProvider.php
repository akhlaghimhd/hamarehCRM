<?php

namespace App\Providers;

use App\Models\Activity;
use App\Models\Task;
use App\Observers\ActivityAuditObserver;
use App\Observers\AuditObserver;
use App\Policies\ActivityPolicy;
use App\Policies\TaskPolicy;
use App\Support\CompanyContext;
use Filament\Support\Facades\FilamentView;
use Filament\View\PanelsRenderHook;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Blade;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->scoped(CompanyContext::class);
    }

    public function boot(): void
    {
        Gate::policy(Activity::class, ActivityPolicy::class);
        Gate::policy(Task::class, TaskPolicy::class);\n        Gate::policy(Attachment::class, AttachmentPolicy::class);
        Activity::observe(ActivityAuditObserver::class);
        Task::observe(AuditObserver::class);

        FilamentView::registerRenderHook(
            PanelsRenderHook::HEAD_END,
            fn (): string => Blade::render('
                <link rel="stylesheet" href="{{ asset(\'css/filament-fa.css?v=81\') }}?v=4">
            ')
        );

        if (! app()->runningInConsole()) {
            $this->app->booted(function () {
                if (! Auth::check()) {
                    return;
                }

                $user = Auth::user();
                $context = app(CompanyContext::class);
                $selectedCompanyId = session('current_company_id');

                if ($selectedCompanyId !== null && ! $context->isAvailableTo($user, (int) $selectedCompanyId)) {
                    $context->clearSelection();
                }

                if (session('current_company_id') === null) {
                    $defaultCompanyId = $context->defaultCompanyId($user);

                    if ($defaultCompanyId !== null) {
                        session(['current_company_id' => $defaultCompanyId]);
                    }
                }
            });
        }
    }
}
