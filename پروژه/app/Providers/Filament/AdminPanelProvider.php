<?php

namespace App\Providers\Filament;

use App\Filament\Pages\SwitchCompany;
use Filament\Http\Middleware\Authenticate;
use Filament\Http\Middleware\AuthenticateSession;
use Filament\Http\Middleware\DisableBladeIconComponents;
use Filament\Http\Middleware\DispatchServingFilamentEvent;
use Filament\Navigation\MenuItem;
use Filament\Pages;
use Filament\Panel;
use Filament\PanelProvider;
use Filament\View\PanelsRenderHook;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Blade;
use Illuminate\Support\Facades\DB;
use Filament\Support\Colors\Color;
use Filament\Widgets;
use Illuminate\Cookie\Middleware\AddQueuedCookiesToResponse;
use Illuminate\Cookie\Middleware\EncryptCookies;
use Illuminate\Foundation\Http\Middleware\VerifyCsrfToken;
use Illuminate\Routing\Middleware\SubstituteBindings;
use Illuminate\Session\Middleware\StartSession;
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
            ->colors([
                'primary' => Color::Amber,
            ])
            ->navigationGroups([
                'بازاریابی (جدید)',
                'فروش (جدید)',
                'نوتوپیا',
                'استخر کاتالوگ (پایه)',
                'سیستم',
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
                    ->visible(function (): bool {
                        $user = Auth::user();

                        if (! $user) {
                            return false;
                        }

                        return DB::table('company_user')
                            ->where('user_id', $user->id)
                            ->count() > 1;
                    })
                    ->sort(10),
            ])
            ->discoverWidgets(in: app_path('Filament/Widgets'), for: 'App\\Filament\\Widgets')
            ->widgets([
                Widgets\AccountWidget::class,
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
            // تم Vite مخصوص پنل (لازم برای Kinetics)
            ->viteTheme('resources/css/filament/admin/theme.css');

        if (class_exists(\Otatechie\Kinetics\KineticsPlugin::class)) {
            $panel = $panel->plugin(\Otatechie\Kinetics\KineticsPlugin::make());
        }

        // فارسی / RTL بعد از پلاگین می‌مانند و اولویت بصری دارند
        return $panel
            ->renderHook(
                PanelsRenderHook::BODY_END,
                fn (): string => '<script src="' . asset('js/fa-digits.js') . '?v=' . @filemtime(public_path('js/fa-digits.js')) . '" defer></script>',
            )
            ->renderHook(
                PanelsRenderHook::STYLES_AFTER,
                fn (): string => '<link rel="stylesheet" href="' . asset('css/filament-fa.css') . '?v=' . @filemtime(public_path('css/filament-fa.css')) . '">'
                    . '<link rel="stylesheet" href="' . asset('css/mf-funnel.css') . '?v=' . @filemtime(public_path('css/mf-funnel.css')) . '">'
                    . '<link rel="stylesheet" href="' . asset('css/opp-workspace.css') . '?v=' . @filemtime(public_path('css/opp-workspace.css')) . '">',
            )
            ->renderHook(
                PanelsRenderHook::BODY_START,
                fn (): string => '<script>document.documentElement.setAttribute("dir","rtl");document.documentElement.lang="fa";</script>',
            )
            ->renderHook(
                PanelsRenderHook::BODY_END,
                fn (): string => Blade::render('@include("filament.hooks.persian-digits")'),
            );
    }
}
