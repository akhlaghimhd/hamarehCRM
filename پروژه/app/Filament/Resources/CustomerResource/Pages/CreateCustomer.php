<?php

namespace App\Filament\Resources\CustomerResource\Pages;

use App\Filament\Resources\CustomerResource;
use App\Models\Company;
use Filament\Resources\Pages\CreateRecord;

class CreateCustomer extends CreateRecord
{
    protected static string $resource = CustomerResource::class;

    protected static ?string $title = 'ایجاد مشتری';

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $companyId = session('current_company_id');

        if (! $companyId) {
            $companyId = Company::query()->value('id');
            if ($companyId) {
                session(['current_company_id' => $companyId]);
            }
        }

        $data['company_id'] = $companyId;

        return $data;
    }
}
