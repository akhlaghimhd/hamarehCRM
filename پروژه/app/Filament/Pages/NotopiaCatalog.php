<?php

namespace App\Filament\Pages;

use App\Filament\Resources\ProductModelResource;
use App\Models\ProductLine;
use App\Models\ProductModel;
use Filament\Pages\Page;
use Filament\Support\Enums\MaxWidth;
use UnitEnum;

class NotopiaCatalog extends Page
{
    protected static ?string $navigationLabel = 'نمای کلی نوتوپیا';

    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-squares-2x2';

    protected static string|UnitEnum|null $navigationGroup = 'کاتالوگ نوتوپیا';

    protected static ?int $navigationSort = 5;

    protected static ?string $title = 'نمای کلی نوتوپیا';

    protected string $view = 'filament.pages.notopia-catalog';

    public static function shouldRegisterNavigation(): bool
    {
        return ProductModelResource::canViewAny();
    }

    public function getMaxContentWidth(): MaxWidth|string|null
    {
        return MaxWidth::Full;
    }

    protected function getViewData(): array
    {
        $line = ProductLine::query()->where('code', 'notopia')->first();

        $models = ProductModel::query()
            ->when($line, fn ($q) => $q->where('product_line_id', $line->id))
            ->with(['family', 'season'])
            ->withCount(['sizes', 'coverSkus', 'options'])
            ->orderBy('sort')
            ->get();

        return [
            'line' => $line,
            'models' => $models,
        ];
    }
}
