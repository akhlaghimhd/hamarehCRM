<?php

namespace App\Filament\Resources\CustomerResource\Pages;

use App\Filament\Resources\CustomerResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListCustomers extends ListRecords
{
    protected static string $resource = CustomerResource::class;

    protected static ?string $title = 'مشتریان';

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make()
                ->label('ایجاد مشتری')
                ->visible(fn () => CustomerResource::canCreate()),
        ];
    }
}
