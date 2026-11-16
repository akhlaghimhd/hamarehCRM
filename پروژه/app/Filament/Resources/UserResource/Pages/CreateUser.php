<?php

namespace App\Filament\Resources\UserResource\Pages;

use App\Actions\Identity\UpdateCompanyMembership;
use App\Models\AuditEvent;
use Illuminate\Support\Facades\DB;
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
        $roleIds = array_values(array_unique(array_map('intval', $data['role_ids'] ?? [])));
        $managerUserId = filled($data['manager_user_id'] ?? null) ? (int) $data['manager_user_id'] : null;

        if (! $companyId) {
            throw ValidationException::withMessages([
                'role_ids' => 'شرکت جاری معتبر نیست؛ ابتدا شرکت مجاز را انتخاب کنید.',
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

        if ($managerUserId !== null) {
            app(UpdateCompanyMembership::class)->assertValidManagerAssignment((int) $companyId, 0, $managerUserId);
        }

        $this->companyId = (int) $companyId;
        $this->roleIds = $roleIds;
        $this->managerUserId = $managerUserId;

        unset($data['role_ids'], $data['manager_user_id']);

        return $data;
    }

    protected ?bool $hasDatabaseTransactions = true;

    protected function afterCreate(): void
    {
        $this->record->companies()->syncWithoutDetaching([
            $this->companyId => [
                'role_id' => $this->roleIds[0],
                'is_default' => true,
            ],
        ]);

        AuditEvent::query()->create([
            'company_id' => $this->companyId,
            'auditable_type' => $this->record->getMorphClass(),
            'auditable_id' => $this->record->id,
            'actor_user_id' => auth()->id(),
            'event' => 'membership_added',
            'old_values' => [],
            'new_values' => [
                'name' => $this->record->name,
                'username' => $this->record->username,
                'email' => $this->record->email,
                'role_ids' => $this->roleIds,
                'manager_user_id' => $this->managerUserId,
                'is_default' => true,
            ],
            'metadata' => ['source' => 'user_creation'],
            'occurred_at' => now(),
        ]);

        app(UpdateCompanyMembership::class)->handle(
            $this->companyId,
            (int) $this->record->id,
            $this->roleIds,
            $this->managerUserId,
            auth()->id(),
        );
    }

    public ?int $companyId = null;

    /** @var array<int, int> */
    public array $roleIds = [];

    public ?int $managerUserId = null;
}
