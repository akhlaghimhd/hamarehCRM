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
        DatePicker::configureUsing(fn (DatePicker $field) => $field->jalali());
        DateTimePicker::configureUsing(fn (DateTimePicker $field) => $field->jalali());

        $isDateField = static fn (string $name): bool => (bool) preg_match(
            '/(?:^|[._])[^.]+_(?:at|on|date)$|(?:^|[._])(?:valid_until|date)$/',
            $name,
        );

        TextColumn::configureUsing(static function (TextColumn $column) use ($isDateField): void {
            $name = $column->getName();
            if (! $isDateField($name)) {
                return;
            }
            str_ends_with($name, '_at') ? $column->jalaliDateTime() : $column->jalaliDate();
        });

        TextEntry::configureUsing(static function (TextEntry $entry) use ($isDateField): void {
            $name = $entry->getName();
            if (! $isDateField($name)) {
                return;
            }
            str_ends_with($name, '_at') ? $entry->jalaliDateTime() : $entry->jalaliDate();
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
                NavigationGroup::make('بازاریابی')->icon('heroicon-o-megaphone')->collapsible(true),
                NavigationGroup::make('فروش و سفارش')->icon('heroicon-o-shopping-cart')->collapsible(true),
                NavigationGroup::make('کار تیمی')->icon('heroicon-o-user-group')->collapsible(true),
                NavigationGroup::make('کاتالوگ نوتوپیا')->icon('heroicon-o-cube')->collapsible(true),
                NavigationGroup::make('عملیات')->icon('heroicon-o-cog-6-tooth')->collapsible(true),
                NavigationGroup::make('سازمان')->icon('heroicon-o-building-office-2')->collapsible(true),
                NavigationGroup::make('سیستم')->icon('heroicon-o-shield-check')->collapsible(true),
            ])
            ->discoverResources(in: app_path('Filament/Resources'), for: 'App\Filament\Resources')
            ->discoverPages(in: app_path('Filament/Pages'), for: 'App\Filament\Pages')
            ->pages([
                Pages\Dashboard::class,
                SwitchCompany::class,
            ])
            ->userMenuItems([
                'switch-company' => MenuItem::make()
                    ->label('تعویض شرکت')
                    ->url(fn (): string => SwitchCompany::getUrl())
                    ->icon('heroicon-o-building-office-2')
                    ->visible(fn (): bool => SwitchCompany::canAccess())
                    ->sort(10),
            ])
            ->databaseNotifications()
            ->databaseNotificationsPolling('30s')
            ->discoverWidgets(in: app_path('Filament/Widgets'), for: 'App\Filament\Widgets')
            ->widgets([
                Widgets\AccountWidget::class,
                CrmStatsOverview::class,
                FollowUpNeededWidget::class,
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
                PanelsRenderHook::BODY_END,
                fn (): string => '<script src="' . asset('js/fa-digits.js') . '?v=' . @filemtime(public_path('js/fa-digits.js')) . '" defer></script>',
            )
            ->renderHook(
                PanelsRenderHook::STYLES_AFTER,
                fn (): string => <<<'CSS'
<style>
.fi-sidebar-nav{padding-block:.35rem!important}
.fi-sidebar-group{margin-block:0!important}
.fi-sidebar-group-btn,.fi-sidebar-group-button{min-height:1.85rem!important;padding-block:.2rem!important;font-size:.8rem!important}
.fi-sidebar-group-items{gap:0!important;padding-inline-start:.35rem!important;border-inline-start:2px solid color-mix(in srgb, var(--gray-300) 70%, transparent);margin-inline-start:.55rem!important}
.fi-sidebar-item-btn,.fi-sidebar-item-button{min-height:1.7rem!important;padding-block:.15rem!important;font-size:.78rem!important}
.fi-sc-form,.fi-fo-form,.fi-form{gap:.65rem!important}
.fi-section-header{padding-block:.35rem!important;padding-inline:.65rem!important}
.fi-section-content-ctn,.fi-section-content{padding:.55rem .7rem .65rem!important}
.fi-section-content,.fi-fo-component-ctn{gap:.7rem!important}
.fi-fo-field-wrp{gap:.28rem!important}
.fi-fo-field-wrp-label,.fi-fo-field-wrp > label{margin-top:.15rem!important;margin-bottom:0!important;font-size:.8rem!important;line-height:1.3!important}
.fi-fo-field-wrp-hint,.fi-fo-field-wrp-helper-text,.fi-fo-field-wrp-helper{display:none!important}
.fi-input-wrp:not(:has(textarea)),
.fi-select-input,
.fi-fo-select,
input.fi-input:not(textarea),
select.fi-select-input{
  min-height:2.25rem!important;
  height:2.25rem!important;
}
.fi-input-wrp:not(:has(textarea)){
  padding-block:0!important;
  align-items:center!important;
}
.fi-input-wrp:not(:has(textarea)) input.fi-input,
.fi-input-wrp:not(:has(textarea)) .fi-input:not(textarea){
  min-height:2.25rem!important;
  height:2.25rem!important;
  padding-block:0!important;
  padding-inline:.65rem!important;
  font-size:.85rem!important;
  line-height:2.25rem!important;
}
.fi-input-wrp:has(textarea),
.fi-fo-textarea,
.fi-fo-field-wrp:has(textarea) .fi-input-wrp{
  height:auto!important;
  min-height:0!important;
  align-items:stretch!important;
  padding-block:0!important;
}
textarea.fi-input,
.fi-input-wrp textarea,
.fi-input-wrp textarea.fi-input{
  min-height:2.75rem!important;
  height:auto!important;
  max-height:none!important;
  line-height:1.45!important;
  padding-block:.35rem!important;
  padding-inline:.65rem!important;
  overflow:auto!important;
  resize:vertical!important;
}
.fi-sc .fi-grid,
.fi-fo-form .fi-grid,
.fi-sc-form .fi-grid{
  align-items:stretch!important;
}
.fi-sc .fi-section,
.fi-fo-form .fi-section,
.fi-sc-form .fi-section{
  height:100%!important;
  display:flex!important;
  flex-direction:column!important;
}
.fi-sc .fi-section-content-ctn,
.fi-fo-form .fi-section-content-ctn,
.fi-sc-form .fi-section-content-ctn{
  flex:1 1 auto!important;
}
.fi-btn:not(.fi-icon-btn){min-height:2.25rem!important;padding-block:.35rem!important;font-size:.82rem!important}
.fi-page-main{gap:.55rem!important}
.fi-main{padding-inline:1rem!important}
.hm-amount-input{letter-spacing:.02em}
/* جدول: دکمه‌های آیکن تولبار (فیلتر/ستون) هم‌اندازه و بدون بیرون‌زدگی */
.fi-ta-header-toolbar .fi-icon-btn,
.fi-ta-header .fi-icon-btn,
.fi-ta-filters-dropdown-trigger-btn,
.fi-ta-col-toggle > .fi-icon-btn,
.fi-dropdown-trigger.fi-icon-btn{
  min-height:2rem!important;
  height:2rem!important;
  width:2rem!important;
  max-height:2rem!important;
  padding:0!important;
  overflow:hidden!important;
  display:inline-flex!important;
  align-items:center!important;
  justify-content:center!important;
}
.fi-ta-header-toolbar .fi-icon-btn svg,
.fi-ta-header .fi-icon-btn svg,
.fi-dropdown-trigger.fi-icon-btn svg{
  width:1.15rem!important;
  height:1.15rem!important;
  max-width:1.15rem!important;
  max-height:1.15rem!important;
}
/* نشانگر فیلتر فعال: داخل دکمه بماند */
.fi-ta-filters-dropdown-trigger-btn .fi-badge,
.fi-ta-filters-trigger .fi-badge{
  position:absolute!important;
  inset-inline-end:-.15rem!important;
  inset-block-start:-.15rem!important;
  min-width:1rem!important;
  height:1rem!important;
  padding:0 .2rem!important;
  font-size:.65rem!important;
  line-height:1rem!important;
}
</style>
CSS
            );
    }
}
