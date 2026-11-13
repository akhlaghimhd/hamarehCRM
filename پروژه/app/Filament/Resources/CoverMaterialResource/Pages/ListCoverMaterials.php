<?php

namespace App\Filament\Resources\CoverMaterialResource\Pages;

use App\Filament\Concerns\HasNotopiaBreadcrumbs;
use App\Filament\Resources\CoverMaterialResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListCoverMaterials extends ListRecords
{
    use HasNotopiaBreadcrumbs;

    protected static string $resource = CoverMaterialResource::class;

    protected static ?string $title = 'جنس جلد';

    public function getBreadcrumbs(): array
    {
        return $this->coverSettingsBreadcrumbs('جنس جلد');
    }

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make()
                ->label('ایجاد')
                ->visible(fn () => CoverMaterialResource::canCreate()),
        ];
    }
}
