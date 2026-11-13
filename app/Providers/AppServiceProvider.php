<?php

namespace App\Providers;

use App\Models\Company;
use Filament\Support\Facades\FilamentView;
use Filament\View\PanelsRenderHook;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Blade;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        //
    }

    public function boot(): void
    {
        FilamentView::registerRenderHook(
            PanelsRenderHook::HEAD_END,
            fn (): string => Blade::render('
                <link rel="stylesheet" href="{{ asset(\'css/filament-fa.css?v=81\') }}?v=4">
            ')
        );

        if (! app()->runningInConsole()) {
            $this->app->booted(function () {
                if (Auth::check() && ! session()->has('current_company_id')) {
                    $user = Auth::user();
                    $company = method_exists($user, 'defaultCompany')
                        ? $user->defaultCompany()
                        : Company::query()->first();

                    if ($company) {
                        session(['current_company_id' => $company->id]);
                    }
                }
            });
        }
    }
}
