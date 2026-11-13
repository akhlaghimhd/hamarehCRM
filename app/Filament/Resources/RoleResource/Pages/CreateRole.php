<?php

namespace App\Filament\Resources\RoleResource\Pages;

use App\Filament\Resources\RoleResource;
use App\Models\Company;
use Filament\Resources\Pages\CreateRecord;

class CreateRole extends CreateRecord
{
    protected static string $resource = RoleResource::class;

    protected static ?string $title = 'ایجاد نقش';

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $data['company_id'] = session('current_company_id')
            ?? Company::query()->value('id');

        return $data;
    }
}
