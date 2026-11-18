<?php

namespace App\Filament\Resources\SalesRepresentativeResource\Pages;

use App\Filament\Resources\SalesRepresentativeResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListSalesRepresentatives extends ListRecords
{
    protected static string $resource = SalesRepresentativeResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make()->label('نماینده جدید'),
        ];
    }
}
