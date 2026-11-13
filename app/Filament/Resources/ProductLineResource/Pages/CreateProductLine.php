<?php

namespace App\Filament\Resources\ProductLineResource\Pages;

use App\Filament\Resources\ProductLineResource;
use App\Models\Company;
use Filament\Resources\Pages\CreateRecord;

class CreateProductLine extends CreateRecord
{
    protected static string $resource = ProductLineResource::class;

    protected static ?string $title = 'ایجاد محصول';

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $data['company_id'] = session('current_company_id') ?? Company::query()->value('id');

        return $data;
    }
}
