<?php

namespace App\Filament\Resources\CompanyFinancialIdentityResource\Pages;

use App\Actions\Identity\SaveCompanyFinancialIdentity;
use App\Filament\Resources\CompanyFinancialIdentityResource;
use App\Models\Company;
use App\Models\User;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Database\Eloquent\Model;

class CreateCompanyFinancialIdentity extends CreateRecord
{
    protected static string $resource = CompanyFinancialIdentityResource::class;

    protected function handleRecordCreation(array $data): Model
    {
        $actor = auth()->user();
        abort_unless($actor instanceof User, 403);
        $company = Company::query()->findOrFail($data['company_id'] ?? null);

        return app(SaveCompanyFinancialIdentity::class)->handle(null, $company, $data, $actor);
    }
}
