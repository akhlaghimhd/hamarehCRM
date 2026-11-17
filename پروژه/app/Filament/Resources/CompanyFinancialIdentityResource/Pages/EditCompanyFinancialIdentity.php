<?php

namespace App\Filament\Resources\CompanyFinancialIdentityResource\Pages;

use App\Actions\Identity\SaveCompanyFinancialIdentity;
use App\Filament\Resources\CompanyFinancialIdentityResource;
use App\Models\CompanyFinancialIdentity;
use App\Models\User;
use Filament\Resources\Pages\EditRecord;
use Illuminate\Database\Eloquent\Model;

class EditCompanyFinancialIdentity extends EditRecord
{
    protected static string $resource = CompanyFinancialIdentityResource::class;

    protected function handleRecordUpdate(Model $record, array $data): Model
    {
        $actor = auth()->user();
        abort_unless($actor instanceof User && $record instanceof CompanyFinancialIdentity, 403);
        unset($data['company_id']);
        $data['type'] = $record->type;

        return app(SaveCompanyFinancialIdentity::class)->handle($record, $record->company, $data, $actor);
    }
}
