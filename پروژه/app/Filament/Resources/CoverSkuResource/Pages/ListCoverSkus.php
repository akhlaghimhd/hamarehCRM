<?php

namespace App\Filament\Resources\CoverSkuResource\Pages;

use App\Filament\Concerns\HasNotopiaBreadcrumbs;
use App\Filament\Resources\CoverColorResource;
use App\Filament\Resources\CoverMaterialResource;
use App\Filament\Resources\CoverSkuResource;
use App\Filament\Resources\CoverStructureResource;
use Filament\Actions\Action;
use Filament\Actions\ActionGroup;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListCoverSkus extends ListRecords
{
    use HasNotopiaBreadcrumbs;

    protected static string $resource = CoverSkuResource::class;

    protected static ?string $title = 'تعریف جلد';

    public function getBreadcrumbs(): array
    {
        return $this->notopiaBreadcrumbs([
            '' => 'تعریف جلد',
        ]);
    }

    protected function getHeaderActions(): array
    {
        return [
            ActionGroup::make([
                Action::make('materials')
                    ->label('جنس جلد')
                    ->icon('heroicon-o-squares-plus')
                    ->url(CoverMaterialResource::getUrl('index')),
                Action::make('structures')
                    ->label('مدل ساخت (یک‌تکه / دوتکه)')
                    ->icon('heroicon-o-rectangle-group')
                    ->url(CoverStructureResource::getUrl('index')),
                Action::make('colors')
                    ->label('رنگ‌های جلد')
                    ->icon('heroicon-o-swatch')
                    ->url(CoverColorResource::getUrl('index')),
            ])
                ->label('تنظیمات جلد')
                ->icon('heroicon-o-cog-6-tooth')
                ->button()
                ->color('gray'),

            CreateAction::make()
                ->label('جلد جدید')
                ->visible(fn () => CoverSkuResource::canCreate()),
        ];
    }
}
