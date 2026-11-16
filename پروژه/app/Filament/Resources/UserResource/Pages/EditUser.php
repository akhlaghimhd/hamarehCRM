<?php

namespace App\Filament\Resources\UserResource\Pages;

use App\Actions\Identity\UpdateCompanyMembership;
use App\Actions\Identity\UpdateUserAccount;
use App\Models\AuditEvent;
use App\Filament\Resources\UserResource;
use App\Models\Role;
use App\Models\User;
use Filament\Actions\ViewAction;
use Filament\Resources\Pages\EditRecord;
use Illuminate\Auth\Access\AuthorizationException;
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

        $actor = auth()->user();

        if (! $actor instanceof User) {
            throw new AuthorizationException('برای ویرایش کاربر باید وارد حساب کاربری شوید.');
        }

        app(UpdateUserAccount::class)->assertCanChangeStatus(
            $this->record,
            $data,
            $actor,
            (int) $companyId,
        );

        $this->wasActive = (bool) $this->record->is_active;
        $this->originalProfile = [
            'name' => $this->record->name,
            'username' => $this->record->username,
            'email' => $this->record->email,
        ];

        app(UpdateCompanyMembership::class)->assertValidManagerAssignment((int) $companyId, (int) $this->record->id, $managerUserId);

        $this->companyId = (int) $companyId;
        $this->roleIds = $roleIds;
        $this->managerUserId = $managerUserId;

        unset($data['role_ids'], $data['manager_user_id']);

        return $data;
    }

    protected ?bool $hasDatabaseTransactions = true;

    protected function afterSave(): void
    {
        $actor = auth()->user();

        if (! $actor instanceof User) {
            throw new AuthorizationException('برای ویرایش کاربر باید وارد حساب کاربری شوید.');
        }

        app(UpdateUserAccount::class)->recordStatusChange(
            $this->record,
            $this->wasActive,
            $actor,
            $this->companyId,
        );

        $newProfile = [
            'name' => $this->record->name,
            'username' => $this->record->username,
            'email' => $this->record->email,
        ];
        $oldProfileValues = [];
        $newProfileValues = [];

        foreach ($newProfile as $key => $value) {
            if (($this->originalProfile[$key] ?? null) !== $value) {
                $oldProfileValues[$key] = $this->originalProfile[$key] ?? null;
                $newProfileValues[$key] = $value;
            }
        }

        if ($newProfileValues !== []) {
            AuditEvent::query()->create([
                'company_id' => $this->companyId,
                'auditable_type' => $this->record->getMorphClass(),
                'auditable_id' => $this->record->id,
                'actor_user_id' => $actor->id,
                'event' => 'profile_updated',
                'old_values' => $oldProfileValues,
                'new_values' => $newProfileValues,
                'metadata' => ['source' => 'company_user_management'],
                'occurred_at' => now(),
            ]);
        }

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

    public bool $wasActive = true;

    /** @var array<string, string|null> */
    public array $originalProfile = [];

    /** @var array<int, int> */
    public array $roleIds = [];

    public ?int $managerUserId = null;
}
