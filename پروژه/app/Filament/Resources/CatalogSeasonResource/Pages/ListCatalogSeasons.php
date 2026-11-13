<?php

namespace App\Filament\Resources\CatalogSeasonResource\Pages;

use App\Filament\Resources\CatalogSeasonResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListCatalogSeasons extends ListRecords
{
    protected static string $resource = CatalogSeasonResource::class;

    protected function getHeaderActions(): array
    {
        return [CreateAction::make()->label('کالکشن جدید')];
    }
}
