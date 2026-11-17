<?php

namespace App\Filament\Resources\CompanyResource\Pages;

use App\Actions\Identity\CreateCompanyForUser;
use App\Filament\Resources\CompanyResource;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Database\Eloquent\Model;

class CreateCompany extends CreateRecord
{
    protected static string $resource = CompanyResource::class;

    protected static ?string $title = 'ایجاد شرکت';

    protected function handleRecordCreation(array $data): Model
    {
        return app(CreateCompanyForUser::class)->handle(
            $data,
            auth()->user(),
        );
    }
}
