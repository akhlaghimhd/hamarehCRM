<?php

namespace App\Filament\Resources\UserResource\Pages;

use App\Filament\Resources\UserResource;
use App\Models\Company;
use Filament\Actions\DeleteAction;
use Filament\Actions\ViewAction;
use Filament\Resources\Pages\EditRecord;

class EditUser extends EditRecord
{
    protected static string $resource = UserResource::class;

    protected static ?string $title = 'ویرایش کاربر';

    protected function mutateFormDataBeforeFill(array $data): array
    {
        $companyId = session('current_company_id') ?? Company::query()->value('id');

        if ($companyId) {
            $pivot = $this->record->companies()
                ->where('companies.id', $companyId)
                ->first()?->pivot;

            $data['role_id'] = $pivot?->role_id;
        }

        return $data;
    }

    protected function mutateFormDataBeforeSave(array $data): array
    {
        $this->roleId = $data['role_id'] ?? null;
        unset($data['role_id']);

        return $data;
    }

    protected function afterSave(): void
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

    protected function getHeaderActions(): array
    {
        return [
            ViewAction::make()->label('مشاهده'),
            DeleteAction::make()->label('حذف'),
        ];
    }

    public mixed $roleId = null;
}
