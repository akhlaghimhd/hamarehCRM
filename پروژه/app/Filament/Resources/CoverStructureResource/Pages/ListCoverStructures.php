<?php

namespace App\Filament\Resources\CoverStructureResource\Pages;

use App\Filament\Concerns\HasNotopiaBreadcrumbs;
use App\Filament\Resources\CoverStructureResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListCoverStructures extends ListRecords
{
    use HasNotopiaBreadcrumbs;

    protected static string $resource = CoverStructureResource::class;

    protected static ?string $title = 'مدل ساخت جلد';

    public function getBreadcrumbs(): array
    {
        return $this->coverSettingsBreadcrumbs('مدل ساخت جلد');
    }

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make()
                ->label('ایجاد')
                ->visible(fn () => CoverStructureResource::canCreate()),
        ];
    }
}
