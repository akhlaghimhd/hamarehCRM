<?php

namespace App\Filament\Resources\UserResource\Pages;

use App\Filament\Resources\UserResource;
use App\Models\Role;
use Filament\Actions\ViewAction;
use Filament\Resources\Pages\EditRecord;
use Illuminate\Validation\ValidationException;

class EditUser extends EditRecord
{
    protected static string $resource = UserResource::class;

    protected static ?string $title = 'ویرایش کاربر';

    protected function mutateFormDataBeforeFill(array $data): array
    {
        $companyId = auth()->user()?->currentCompanyId();

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
        $companyId = auth()->user()?->currentCompanyId();
        $roleId = $data['role_id'] ?? null;

        if (! $companyId || ! $this->record->companies()
            ->where('companies.id', $companyId)
            ->exists()) {
            throw ValidationException::withMessages([
                'role_id' => 'عضویت کاربر در شرکت جاری معتبر نیست.',
            ]);
        }

        if (! $roleId || ! Role::query()
            ->where('company_id', $companyId)
            ->whereKey($roleId)
            ->exists()) {
            throw ValidationException::withMessages([
                'role_id' => 'نقش انتخاب‌شده متعلق به شرکت جاری نیست.',
            ]);
        }

        $this->companyId = (int) $companyId;
        $this->roleId = (int) $roleId;

        unset($data['role_id']);

        return $data;
    }

    protected function afterSave(): void
    {
        $this->record->companies()->syncWithoutDetaching([
            $this->companyId => [
                'role_id' => $this->roleId,
            ],
        ]);
    }

    protected function getHeaderActions(): array
    {
        return [
            ViewAction::make()->label('مشاهده'),
        ];
    }

    public ?int $companyId = null;

    public ?int $roleId = null;
}
