<?php

namespace App\Filament\Resources\CustomerResource\Pages;

use App\Filament\Resources\CustomerResource;
use Filament\Actions\EditAction;
use Filament\Resources\Pages\ViewRecord;

class ViewCustomer extends ViewRecord
{
    protected static string $resource = CustomerResource::class;

    protected static ?string $title = 'مشاهده مشتری';

    protected function getHeaderActions(): array
    {
        return [
            EditAction::make()->label('ویرایش'),
        ];
    }
}
