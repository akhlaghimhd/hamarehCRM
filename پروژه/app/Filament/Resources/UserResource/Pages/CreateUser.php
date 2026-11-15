<?php

namespace App\Filament\Resources\UserResource\Pages;

use App\Filament\Resources\UserResource;
use App\Models\Role;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Validation\ValidationException;

class CreateUser extends CreateRecord
{
    protected static string $resource = UserResource::class;

    protected static ?string $title = 'ایجاد کاربر';

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $companyId = auth()->user()?->currentCompanyId();
        $roleId = $data['role_id'] ?? null;

        if (! $companyId) {
            throw ValidationException::withMessages([
                'role_id' => 'شرکت جاری معتبر نیست؛ ابتدا شرکت مجاز را انتخاب کنید.',
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

    protected function afterCreate(): void
    {
        $this->record->companies()->syncWithoutDetaching([
            $this->companyId => [
                'role_id' => $this->roleId,
                'is_default' => true,
            ],
        ]);
    }

    public ?int $companyId = null;

    public ?int $roleId = null;
}
