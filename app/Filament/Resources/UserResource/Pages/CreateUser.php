<?php

namespace App\Filament\Resources\UserResource\Pages;

use App\Filament\Resources\UserResource;
use App\Models\Company;
use Filament\Resources\Pages\CreateRecord;

class CreateUser extends CreateRecord
{
    protected static string $resource = UserResource::class;

    protected static ?string $title = 'ایجاد کاربر';

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $this->roleId = $data['role_id'] ?? null;
        unset($data['role_id']);

        return $data;
    }

    protected function afterCreate(): void
    {
        $companyId = session('current_company_id') ?? Company::query()->value('id');

        if ($companyId) {
            $this->record->companies()->syncWithoutDetaching([
                $companyId => [
                    'role_id' => $this->roleId,
                    'is_default' => true,
                ],
            ]);
        }
    }

    public mixed $roleId = null;
}
