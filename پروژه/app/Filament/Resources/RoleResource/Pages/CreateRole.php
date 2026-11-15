<?php

namespace App\Filament\Resources\RoleResource\Pages;

use App\Filament\Resources\RoleResource;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Validation\ValidationException;

class CreateRole extends CreateRecord
{
    protected static string $resource = RoleResource::class;

    protected static ?string $title = 'ایجاد نقش';

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $companyId = auth()->user()?->currentCompanyId();

        if (! $companyId) {
            throw ValidationException::withMessages([
                'name' => 'شرکت جاری معتبر نیست؛ ابتدا شرکت مجاز را انتخاب کنید.',
            ]);
        }

        $data['company_id'] = $companyId;

        return $data;
    }
}
