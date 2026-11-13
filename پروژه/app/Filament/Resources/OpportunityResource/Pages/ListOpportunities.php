<?php

namespace App\Filament\Resources\OpportunityResource\Pages;

use App\Filament\Resources\OpportunityResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListOpportunities extends ListRecords
{
    protected static string $resource = OpportunityResource::class;

    protected static ?string $title = 'فرصت‌ها';

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make()
                ->label('ایجاد فرصت')
                ->visible(fn () => OpportunityResource::canCreate()),
        ];
    }
}
