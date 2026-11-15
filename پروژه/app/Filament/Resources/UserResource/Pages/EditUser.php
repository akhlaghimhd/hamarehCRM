<?php

namespace App\Filament\Resources\UserResource\Pages;

use App\Actions\Identity\UpdateCompanyMembership;
use App\Filament\Resources\UserResource;
use App\Models\Role;
use Filament\Actions\ViewAction;
use Filament\Resources\Pages\EditRecord;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class EditUser extends EditRecord
{
    protected static string $resource = UserResource::class;

    protected static ?string $title = 'ویرایش کاربر';

    protected function mutateFormDataBeforeFill(array $data): array
    {
        $companyId = auth()->user()?->currentCompanyId();

        if ($companyId) {
            $membership = DB::table('company_user')
                ->where('company_id', $companyId)
                ->where('user_id', $this->record->id)
                ->first();

            $roleIds = $membership
                ? DB::table('company_user_role')
                    ->where('company_user_id', $membership->id)
                    ->orderBy('id')
                    ->pluck('role_id')
                    ->map(fn ($id): int => (int) $id)
                    ->all()
                : [];

            if ($roleIds === [] && $membership?->role_id) {
                $roleIds = [(int) $membership->role_id];
            }

            $data['role_ids'] = $roleIds;
            $data['manager_user_id'] = $membership?->manager_user_id;
        }

        return $data;
    }

    protected function mutateFormDataBeforeSave(array $data): array
    {
        $companyId = auth()->user()?->currentCompanyId();
        $roleIds = array_values(array_unique(array_map('intval', $data['role_ids'] ?? [])));
        $managerUserId = filled($data['manager_user_id'] ?? null) ? (int) $data['manager_user_id'] : null;

        if (! $companyId || ! $this->record->companies()
            ->where('companies.id', $companyId)
            ->exists()) {
            throw ValidationException::withMessages([
                'role_ids' => 'عضویت کاربر در شرکت جاری معتبر نیست.',
            ]);
        }

        if ($roleIds === [] || Role::query()
            ->where('company_id', $companyId)
            ->whereIn('id', $roleIds)
            ->count() !== count($roleIds)) {
            throw ValidationException::withMessages([
                'role_ids' => 'حداقل یک نقش معتبر از شرکت جاری انتخاب کنید.',
            ]);
        }

        $this->companyId = (int) $companyId;
        $this->roleIds = $roleIds;
        $this->managerUserId = $managerUserId;

        unset($data['role_ids'], $data['manager_user_id']);

        return $data;
    }

    protected function afterSave(): void
    {
        app(UpdateCompanyMembership::class)->handle(
            $this->companyId,
            (int) $this->record->id,
            $this->roleIds,
            $this->managerUserId,
            auth()->id(),
        );
    }

    protected function getHeaderActions(): array
    {
        return [
            ViewAction::make()->label('مشاهده'),
        ];
    }

    public ?int $companyId = null;

    /** @var array<int, int> */
    public array $roleIds = [];

    public ?int $managerUserId = null;
}
