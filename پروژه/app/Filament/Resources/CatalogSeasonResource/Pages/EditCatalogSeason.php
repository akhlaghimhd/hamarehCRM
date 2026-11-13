<?php

namespace App\Filament\Resources\CatalogSeasonResource\Pages;

use App\Filament\Resources\CatalogSeasonResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditCatalogSeason extends EditRecord
{
    protected static string $resource = CatalogSeasonResource::class;

    protected function getHeaderActions(): array
    {
        return [DeleteAction::make()];
    }
}
