<?php

namespace App\Filament\Resources\CompanyFinancialIdentityResource\Pages;

use App\Filament\Resources\CompanyFinancialIdentityResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListCompanyFinancialIdentities extends ListRecords
{
    protected static string $resource = CompanyFinancialIdentityResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make()->label('افزودن هویت مالی')->visible(fn () => CompanyFinancialIdentityResource::canCreate()),
        ];
    }
}
