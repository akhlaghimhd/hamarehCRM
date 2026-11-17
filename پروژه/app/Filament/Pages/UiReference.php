<?php

namespace App\Filament\Pages;

use Filament\Pages\Page;
use UnitEnum;

class UiReference extends Page
{
    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-swatch';

    protected static ?string $navigationLabel = 'صفحه مرجع UI';

    protected static string|UnitEnum|null $navigationGroup = 'سیستم';

    protected static ?int $navigationSort = 90;

    protected static ?string $title = 'صفحه مرجع طراحی';

    protected static ?string $slug = 'ui-reference';

    protected string $view = 'filament.pages.ui-reference';

    public static function shouldRegisterNavigation(): bool
    {
        return false;
    }

    public static function canAccess(): bool
    {
        return auth()->check();
    }
}
