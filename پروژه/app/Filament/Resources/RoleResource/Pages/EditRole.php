<?php

namespace App\Filament\Resources\RoleResource\Pages;

use App\Actions\Identity\DeleteCompanyRole;
use App\Actions\Identity\UpdateRoleHierarchy;
use App\Models\AuditEvent;
use App\Filament\Resources\RoleResource;
use App\Models\Role;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;
use Illuminate\Validation\ValidationException;

class EditRole extends EditRecord
{
    protected static string $resource = RoleResource::class;

    protected static ?string $title = 'ویرایش نقش';

    protected function mutateFormDataBeforeSave(array $data): array
    {
        $this->originalRoleState = [
            'name' => $this->record->name,
            'label' => $this->record->label,
            'parent_role_id' => $this->record->parent_role_id === null ? null : (int) $this->record->parent_role_id,
            'inherit_permissions' => (bool) $this->record->inherit_permissions,
            'permission_ids' => $this->record->permissions()->pluck('permissions.id')->map(fn ($id): int => (int) $id)->all(),
        ];

        $name = trim((string) ($data['name'] ?? $this->record->name));

        if ($this->record->is_system && $name !== $this->record->name) {
            throw ValidationException::withMessages([
                'name' => 'نام نقش سیستمی قابل تغییر نیست.',
            ]);
        }

        $name = strtolower($name);

        if (! $this->record->is_system && $name === 'admin') {
            throw ValidationException::withMessages([
                'name' => 'نام admin رزرو شده و فقط برای نقش مدیر سیستمی شرکت استفاده می‌شود.',
            ]);
        }

        if (! preg_match('/^[a-z][a-z0-9_-]{0,99}$/', $name)) {
            throw ValidationException::withMessages([
                'name' => 'نام سیستمی نقش باید با حرف انگلیسی کوچک شروع شود و فقط شامل حروف کوچک، عدد، خط تیره یا زیرخط باشد.',
            ]);
        }

        if (Role::query()
            ->where('company_id', $this->record->company_id)
            ->where('name', $name)
            ->where('id', '<>', $this->record->id)
            ->exists()) {
            throw ValidationException::withMessages([
                'name' => 'این نام سیستمی قبلاً در شرکت جاری استفاده شده است.',
            ]);
        }

        $data['name'] = $name;
        unset($data['is_system']);

        $parentRoleId = filled($data['parent_role_id'] ?? null) ? (int) $data['parent_role_id'] : null;

        if ($parentRoleId !== null) {
            if ($parentRoleId === (int) $this->record->id) {
                throw ValidationException::withMessages([
                    'parent_role_id' => 'یک نقش نمی‌تواند والد خودش باشد.',
                ]);
            }

            $cursor = $parentRoleId;
            $visited = [];

            while ($cursor !== null) {
                if ($cursor === (int) $this->record->id || in_array($cursor, $visited, true)) {
                    throw ValidationException::withMessages([
                        'parent_role_id' => 'این انتخاب باعث ایجاد چرخه در سلسله‌مراتب نقش‌ها می‌شود.',
                    ]);
                }

                $visited[] = $cursor;
                $parent = Role::query()
                    ->where('company_id', $this->record->company_id)
                    ->whereKey($cursor)
                    ->first();

                if (! $parent) {
                    throw ValidationException::withMessages([
                        'parent_role_id' => 'نقش والد باید متعلق به همین شرکت باشد.',
                    ]);
                }

                $cursor = $parent->parent_role_id === null ? null : (int) $parent->parent_role_id;
            }
        }

        $this->parentRoleId = $parentRoleId;
        $this->inheritPermissions = (bool) ($data['inherit_permissions'] ?? false);

        unset($data['parent_role_id'], $data['inherit_permissions'], $data['copy_permissions_from_role_id']);

        return $data;
    }

    protected ?bool $hasDatabaseTransactions = true;

    protected function afterSave(): void
    {
        app(UpdateRoleHierarchy::class)->handle(
            $this->record,
            $this->parentRoleId,
            $this->inheritPermissions,
        );

        $role = $this->record->fresh();
        $newRoleState = [
            'name' => $role->name,
            'label' => $role->label,
            'parent_role_id' => $role->parent_role_id === null ? null : (int) $role->parent_role_id,
            'inherit_permissions' => (bool) $role->inherit_permissions,
            'permission_ids' => $role->permissions()->pluck('permissions.id')->map(fn ($id): int => (int) $id)->all(),
        ];

        $oldComparable = $this->originalRoleState;
        $newComparable = $newRoleState;
        sort($oldComparable['permission_ids']);
        sort($newComparable['permission_ids']);

        if ($oldComparable !== $newComparable) {
            AuditEvent::query()->create([
                'company_id' => $role->company_id,
                'auditable_type' => $role->getMorphClass(),
                'auditable_id' => $role->getKey(),
                'actor_user_id' => auth()->id(),
                'event' => 'updated',
                'old_values' => $this->originalRoleState,
                'new_values' => $newRoleState,
                'metadata' => ['source' => 'role_management'],
                'occurred_at' => now(),
            ]);
        }
    }

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make()
                ->label('حذف')
                ->hidden(fn ($record) => $record->is_system)
                ->using(fn (Role $record) => app(DeleteCompanyRole::class)->handle($record, auth()->user()))
                ->successNotificationTitle('نقش حذف شد'),
        ];
    }

    public ?int $parentRoleId = null;

    public bool $inheritPermissions = false;

    /** @var array<string, mixed> */
    protected array $originalRoleState = [];
}
