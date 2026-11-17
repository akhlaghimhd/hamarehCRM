<?php

namespace App\Providers\Filament;

use App\Filament\Pages\SwitchCompany;
use App\Support\CompanyTheme;
use Filament\Http\Middleware\Authenticate;
use Filament\Http\Middleware\AuthenticateSession;
use Filament\Http\Middleware\DisableBladeIconComponents;
use Filament\Http\Middleware\DispatchServingFilamentEvent;
use Filament\Navigation\MenuItem;
use Filament\Pages;
use Filament\Panel;
use Filament\PanelProvider;
use Filament\View\PanelsRenderHook;
use Filament\Widgets;
use Hammadzafar05\MobileBottomNav\MobileBottomNav;
use Illuminate\Cookie\Middleware\AddQueuedCookiesToResponse;
use Illuminate\Cookie\Middleware\EncryptCookies;
use Illuminate\Foundation\Http\Middleware\VerifyCsrfToken;
use Illuminate\Routing\Middleware\SubstituteBindings;
use Illuminate\Session\Middleware\StartSession;
use Illuminate\Support\Facades\Blade;
use Illuminate\View\Middleware\ShareErrorsFromSession;

class AdminPanelProvider extends PanelProvider
{
    public function panel(Panel $panel): Panel
    {
        $panel = $panel
            ->default()
            ->id('admin')
            ->path('admin')
            ->login()
            ->sidebarCollapsibleOnDesktop()
            ->sidebarWidth('15rem')
            ->collapsedSidebarWidth('4.5rem')
            ->collapsibleNavigationGroups()
            ->navigationGroups([
                'مدیریت سازمان',
                'ارتباط با مشتری',
                'فروش و سفارش',
                'کاتالوگ و محصولات',
                'کارها و تیم',
                'گزارش‌ها',
                'تنظیمات',
            ])
            ->discoverResources(in: app_path('Filament/Resources'), for: 'App\\Filament\\Resources')
            ->discoverPages(in: app_path('Filament/Pages'), for: 'App\\Filament\\Pages')
            ->pages([
                Pages\Dashboard::class,
                SwitchCompany::class,
            ])
            ->userMenuItems([
                'switch-company' => MenuItem::make()
                    ->label('تعویض شرکت')
                    ->icon('heroicon-o-building-office-2')
                    ->url(fn (): string => SwitchCompany::getUrl())
                    ->visible(fn (): bool => SwitchCompany::canAccess())
                    ->sort(10),
            ])
            ->discoverWidgets(in: app_path('Filament/Widgets'), for: 'App\\Filament\\Widgets')
            ->widgets([
                Widgets\AccountWidget::class,
            ])
            ->plugins([
                MobileBottomNav::make()
                    ->fromNavigation(5)
                    ->moreButtonLabel('بیشتر'),
            ])
            ->middleware([
                EncryptCookies::class,
                AddQueuedCookiesToResponse::class,
                StartSession::class,
                AuthenticateSession::class,
                ShareErrorsFromSession::class,
                VerifyCsrfToken::class,
                SubstituteBindings::class,
                DisableBladeIconComponents::class,
                DispatchServingFilamentEvent::class,
            ])
            ->authMiddleware([
                Authenticate::class,
            ])
            ->viteTheme('resources/css/filament/admin/theme.css');

        return $panel
            ->renderHook(
                PanelsRenderHook::BODY_END,
                fn (): string => '<script src="' . asset('js/fa-digits.js') . '?v=' . @filemtime(public_path('js/fa-digits.js')) . '" defer></script>',
            )
            ->renderHook(
                PanelsRenderHook::STYLES_AFTER,
                fn (): string => '<link rel="stylesheet" href="' . asset('css/filament-fa.css') . '?v=' . @filemtime(public_path('css/filament-fa.css')) . '">'
            )
            ->renderHook(
                PanelsRenderHook::STYLES_AFTER,
                fn (): string => app(CompanyTheme::class)->styleTag()
            )
            ->renderHook(
                PanelsRenderHook::BODY_START,
                fn (): string => '<script>document.documentElement.setAttribute("dir","rtl");document.documentElement.lang="fa";document.documentElement.dataset.hmDensity=' . json_encode(app(CompanyTheme::class)->density(), JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_AMP | JSON_HEX_QUOT) . ';</script>',
            )
            ->renderHook(
                PanelsRenderHook::BODY_END,
                fn (): string => Blade::render('@include("filament.hooks.persian-digits")'),
            );
    }
}
