<?php

namespace App\Filament\Resources\OpportunityResource\Pages;

use App\Filament\Resources\OpportunityResource;
use Filament\Actions\Action;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListOpportunities extends ListRecords
{
    protected static string $resource = OpportunityResource::class;

    protected static ?string $title = 'فرصت‌ها';

    protected function getHeaderActions(): array
    {
        return [
            Action::make('quickCreate')
                ->label('ثبت سریع')
                ->icon('heroicon-o-bolt')
                ->color('primary')
                ->url(OpportunityResource::getUrl('quick-create'))
                ->visible(fn () => OpportunityResource::canCreate()),
            CreateAction::make()
                ->label('فرم کامل')
                ->color('gray')
                ->visible(fn () => OpportunityResource::canCreate()),
        ];
    }
}
