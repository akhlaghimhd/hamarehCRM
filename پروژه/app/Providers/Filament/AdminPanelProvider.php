<?php

namespace App\Providers\Filament;

use App\Support\Fa;
use Filament\Http\Middleware\Authenticate;
use Filament\Http\Middleware\AuthenticateSession;
use Filament\Http\Middleware\DisableBladeIconComponents;
use Filament\Http\Middleware\DispatchServingFilamentEvent;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\DateTimePicker;
use Filament\Tables\Columns\TextColumn;
use Filament\Infolists\Components\TextEntry;
use Filament\Navigation\MenuItem;
use Filament\Pages;
use Filament\Panel;
use Filament\PanelProvider;
use Filament\Support\Colors\Color;
use Filament\Support\Facades\FilamentView;
use Filament\View\PanelsRenderHook;
use Filament\Widgets;
use Illuminate\Cookie\Middleware\AddQueuedCookiesToResponse;
use Illuminate\Cookie\Middleware\EncryptCookies;
use Illuminate\Foundation\Http\Middleware\PreventRequestForgery;
use Illuminate\Routing\Middleware\SubstituteBindings;
use Illuminate\Session\Middleware\StartSession;
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
            '/(?:^|_)(date|at|until|due|deadline|started|ended|completed|paid|issued|valid)(?:_|$)/i',
            $name,
        );

        TextColumn::configureUsing(function (TextColumn $column) use ($isDateField): void {
            $name = $column->getName();
            if (! is_string($name) || ! $isDateField($name)) {
                return;
            }

            $column->formatStateUsing(function ($state) {
                if (blank($state)) {
                    return $state;
                }

                try {
                    return \Carbon\Carbon::parse($state)->format('Y/m/d');
                } catch (\Throwable) {
                    return $state;
                }
            });
        });

        TextEntry::configureUsing(function (TextEntry $entry) use ($isDateField): void {
            $name = $entry->getName();
            if (! is_string($name) || ! $isDateField($name)) {
                return;
            }

            $entry->formatStateUsing(function ($state) {
                if (blank($state)) {
                    return $state;
                }

                try {
                    return \Carbon\Carbon::parse($state)->format('Y/m/d');
                } catch (\Throwable) {
                    return $state;
                }
            });
        });

        return $panel
            ->default()
            ->id('admin')
            ->path('admin')
            ->login()
            ->brandName('هماره‌سی‌آر‌ام')
            ->font('Vazirmatn')
            ->colors([
                'primary' => Color::Teal,
            ])
            ->discoverResources(in: app_path('Filament/Resources'), for: 'App\\Filament\\Resources')
            ->discoverPages(in: app_path('Filament/Pages'), for: 'App\\Filament\\Pages')
            ->pages([
                Pages\Dashboard::class,
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
                PreventRequestForgery::class,
                SubstituteBindings::class,
                DisableBladeIconComponents::class,
                DispatchServingFilamentEvent::class,
            ])
            ->authMiddleware([
                Authenticate::class,
            ])
            ->navigationGroups([
                \Filament\Navigation\NavigationGroup::make('بازاریابی')->icon('heroicon-o-megaphone'),
                \Filament\Navigation\NavigationGroup::make('فروش و سفارش')->icon('heroicon-o-shopping-cart'),
                \Filament\Navigation\NavigationGroup::make('کار تیمی')->icon('heroicon-o-user-group'),
                \Filament\Navigation\NavigationGroup::make('کاتالوگ نوتوپیا')->icon('heroicon-o-cube'),
                \Filament\Navigation\NavigationGroup::make('عملیات')->icon('heroicon-o-cog-6-tooth'),
                \Filament\Navigation\NavigationGroup::make('سازمان')->icon('heroicon-o-building-office-2'),
                \Filament\Navigation\NavigationGroup::make('سیستم')->icon('heroicon-o-shield-check'),
            ])
            ->userMenuItems([
                MenuItem::make()
                    ->label('تعویض شرکت')
                    ->url(fn (): string => \App\Filament\Pages\SwitchCompany::getUrl())
                    ->icon('heroicon-o-arrows-right-left'),
            ])
            ->renderHook(
                PanelsRenderHook::HEAD_END,
                fn (): string => view('filament.hooks.persian-digits')->render(),
            )
            ->renderHook(
                PanelsRenderHook::STYLES_AFTER,
                fn (): string => <<<'CSS'
<style>
/* —— Compact sidebar groups —— */
.fi-sidebar-nav{gap:.15rem!important}
.fi-sidebar-group{row-gap:.1rem!important;margin-block:.15rem!important}
.fi-sidebar-group-btn,.fi-sidebar-group-button{
  min-height:1.85rem!important;
  padding-block:.2rem!important;
  padding-inline:.45rem!important;
  font-size:.8rem!important;
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

/* —— Form density (global) —— */
.fi-sc-form,.fi-fo-form,.fi-form{gap:.4rem!important}
.fi-section{margin-block:.1rem!important}
.fi-section-header{padding-block:.35rem!important;padding-inline:.65rem!important}
.fi-section-header-heading{font-size:.88rem!important;line-height:1.2!important}
.fi-section-header-description{font-size:.7rem!important;margin-top:.05rem!important;opacity:.85}
.fi-section-content-ctn,.fi-section-content{
  padding:.4rem .6rem .5rem!important;
}
.fi-section-content{gap:.4rem!important;row-gap:.4rem!important}
.fi-fo-component-ctn{gap:.4rem!important}
.fi-fo-field-wrp{gap:.15rem!important}
.fi-fo-field-wrp-label{margin:0!important;padding:0!important}
.fi-fo-field-wrp-label .fi-fo-field-wrp-label-text,
.fi-fo-field-wrp-label span{font-size:.78rem!important;font-weight:600!important}
.fi-fo-field-wrp-hint,.fi-fo-field-wrp-helper-text,.fi-fo-field-wrp-helper{display:none!important}
/* Equal control height for text, select, date */
.fi-input-wrp,
.fi-select-input,
.fi-fo-select,
.fi-fo-date-time-picker,
.fi-fo-date-time-picker-display-text-input,
.fi-input-wrp input,
.fi-input-wrp select,
.fi-input,
input.fi-input,
select.fi-select-input,
.fi-select-input > button,
.fi-fo-select .fi-select-input{
  min-height:2.25rem!important;
  height:2.25rem!important;
}
.fi-input-wrp{
  padding-block:0!important;
  align-items:center!important;
}
.fi-input-wrp input.fi-input,
.fi-input-wrp .fi-input{
  min-height:2.25rem!important;
  height:2.25rem!important;
  padding-block:0!important;
  padding-inline:.65rem!important;
  font-size:.85rem!important;
  line-height:2.25rem!important;
}
textarea.fi-input,
.fi-input-wrp textarea.fi-input{
  min-height:4.5rem!important;
  height:auto!important;
  line-height:1.45!important;
  padding-block:.5rem!important;
}
.fi-fo-select .fi-select-input,
.fi-select-input{
  min-height:2.25rem!important;
  height:2.25rem!important;
}
.fi-btn{min-height:2.25rem!important;padding-block:.35rem!important;font-size:.82rem!important}
.fi-fo-field-wrp-error-message{font-size:.72rem!important;margin-top:.1rem!important}
.fi-page-header{padding-block:.5rem!important}
.fi-page-main{gap:.55rem!important}
.fi-main{padding-inline:1rem!important}
/* Amount field LTR digits area */
.hm-amount-input{letter-spacing:.02em}
</style>
CSS
            );
    }
}
