<?php

namespace App\Providers\Filament;

use App\Filament\Pages\SwitchCompany;
use App\Filament\Widgets\CrmStatsOverview;
use App\Filament\Widgets\FollowUpNeededWidget;
use App\Support\CompanyTheme;
use Filament\Http\Middleware\Authenticate;
use Filament\Http\Middleware\AuthenticateSession;
use Filament\Http\Middleware\DisableBladeIconComponents;
use Filament\Http\Middleware\DispatchServingFilamentEvent;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\DateTimePicker;
use Filament\Infolists\Components\TextEntry;
use Filament\Navigation\MenuItem;
use Filament\Navigation\NavigationGroup;
use Filament\Pages;
use Filament\Panel;
use Filament\PanelProvider;
use Filament\Tables\Columns\TextColumn;
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
        // Use the installed Filament 5 Jalali package for every panel date input.
        DatePicker::configureUsing(fn (DatePicker $field) => $field->jalali());
        DateTimePicker::configureUsing(fn (DateTimePicker $field) => $field->jalali());

        // Date-like fields default to Jalali output, including relation managers and infolists.
        $isDateField = static fn (string $name): bool => (bool) preg_match(
            '/(?:^|[._])[^.]+_(?:at|on|date)$|(?:^|[._])(?:valid_until|date)$/',
            $name,
        );

        TextColumn::configureUsing(static function (TextColumn $column) use ($isDateField): void {
            $name = $column->getName();

            if (! $isDateField($name)) {
                return;
            }

            str_ends_with($name, '_at')
                ? $column->jalaliDateTime()
                : $column->jalaliDate();
        });

        TextEntry::configureUsing(static function (TextEntry $entry) use ($isDateField): void {
            $name = $entry->getName();

            if (! $isDateField($name)) {
                return;
            }

            str_ends_with($name, '_at')
                ? $entry->jalaliDateTime()
                : $entry->jalaliDate();
        });

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
                NavigationGroup::make('بازاریابی')
                    ->icon('heroicon-o-megaphone')
                    ->collapsible(true),
                NavigationGroup::make('فروش و سفارش')
                    ->icon('heroicon-o-shopping-cart')
                    ->collapsible(true),
                NavigationGroup::make('کاتالوگ نوتوپیا')
                    ->icon('heroicon-o-cube')
                    ->collapsible(true),
                NavigationGroup::make('عملیات')
                    ->icon('heroicon-o-cog-6-tooth')
                    ->collapsible(true),
                NavigationGroup::make('کار تیمی')
                    ->icon('heroicon-o-user-group')
                    ->collapsible(true),
                NavigationGroup::make('سازمان')
                    ->icon('heroicon-o-building-office-2')
                    ->collapsible(true),
                NavigationGroup::make('سیستم')
                    ->icon('heroicon-o-shield-check')
                    ->collapsible(true),
            ])
            ->discoverResources(in: app_path('Filament/Resources'), for: 'App\\Filament\\Resources')
            ->discoverPages(in: app_path('Filament/Pages'), for: 'App\\Filament\\Pages')
            ->pages([
                Pages\\Dashboard::class,
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
            ->databaseNotifications()
            ->databaseNotificationsPolling('30s')
            ->discoverWidgets(in: app_path('Filament/Widgets'), for: 'App\\Filament\\Widgets')
            ->widgets([
                Widgets\\AccountWidget::class,
                CrmStatsOverview::class,
                FollowUpNeededWidget::class,
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
            )
            ->renderHook(
                PanelsRenderHook::STYLES_AFTER,
                fn (): string => '<style>
/* Compact sidebar groups — match dense reference layout */
.fi-sidebar-nav{padding-block:.35rem!important}
.fi-sidebar-nav-groups{row-gap:.15rem!important;gap:.15rem!important}
.fi-sidebar-group{margin-block:0!important}
.fi-sidebar-group-btn,.fi-sidebar-group-button,.fi-sidebar-group-label{
  min-height:1.85rem!important;
  padding-block:.2rem!important;
  padding-inline:.55rem!important;
  font-size:.8rem!important;
  font-weight:700!important;
  gap:.4rem!important;
}
.fi-sidebar-group-icon,.fi-sidebar-group-btn .fi-icon,.fi-sidebar-group-button .fi-icon{
  width:1.05rem!important;height:1.05rem!important;
}
.fi-sidebar-group-items{
  gap:0!important;
  row-gap:0!important;
  margin:0!important;
  padding-inline-start:.35rem!important;
  border-inline-start:2px solid color-mix(in srgb, var(--gray-300) 70%, transparent);
  margin-inline-start:.55rem!important;
}
.fi-sidebar-item{margin:0!important}
.fi-sidebar-item-btn,.fi-sidebar-item-button{
  min-height:1.7rem!important;
  padding-block:.15rem!important;
  padding-inline:.45rem .55rem!important;
  font-size:.78rem!important;
  gap:.4rem!important;
}
.fi-sidebar-item-icon,.fi-sidebar-item-btn .fi-icon,.fi-sidebar-item-button .fi-icon{
  width:.95rem!important;height:.95rem!important;
}
.fi-sidebar-item-label{line-height:1.2!important}
</style>',
            );
    }
}
