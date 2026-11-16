<?php

namespace App\Filament\Resources\RoleResource\Pages;

use App\Actions\Identity\DeleteCompanyRole;
use App\Actions\Identity\UpdateRoleHierarchy;
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

    protected function afterSave(): void
    {
        app(UpdateRoleHierarchy::class)->handle(
            $this->record,
            $this->parentRoleId,
            $this->inheritPermissions,
        );
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
}
