<?php

namespace App\Filament\Pages;

use App\Filament\Concerns\HasNotopiaBreadcrumbs;
use App\Filament\Resources\ProductLineResource;

use App\Models\ProductLine;
use Filament\Pages\Page;
use UnitEnum;

class NotopiaCatalog extends Page
{
    use HasNotopiaBreadcrumbs;

    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-home';

    protected static ?string $navigationLabel = 'نمای کلی نوتوپیا';

    protected static ?string $title = 'کاتالوگ نوتوپیا';

    protected static string|UnitEnum|null $navigationGroup = 'نوتوپیا';

    protected static ?int $navigationSort = 0;

    protected static ?string $slug = 'notopia-catalog';

    protected string $view = 'filament.pages.notopia-catalog';

    public static function shouldRegisterNavigation(): bool
    {
        return true;
    }

    public function getBreadcrumbs(): array
    {
        return [
            ProductLineResource::getUrl('index') => 'محصولات',
            '' => 'نوتوپیا',
        ];
    }

    public function getNotopia(): ?ProductLine
    {
        return ProductLine::query()->where('code', 'notopia')->first();
    }
}
