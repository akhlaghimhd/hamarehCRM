<?php

namespace App\Filament\Resources\OpportunityResource\Pages;

use App\Filament\Resources\OpportunityResource;
use Filament\Resources\Pages\CreateRecord;

class CreateOpportunity extends CreateRecord
{
    protected static string $resource = OpportunityResource::class;

    protected static ?string $title = 'ایجاد فرصت';

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $data['owner_user_id'] = $data['owner_user_id'] ?? auth()->id();

        return $data;
    }
}
