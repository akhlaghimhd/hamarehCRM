<?php

namespace App\Filament\Resources\RoleResource\Pages;

use App\Actions\Identity\UpdateRoleHierarchy;
use App\Filament\Resources\RoleResource;
use App\Models\Role;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Validation\ValidationException;

class CreateRole extends CreateRecord
{
    protected static string $resource = RoleResource::class;

    protected static ?string $title = 'ایجاد نقش';

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $name = trim((string) ($data['name'] ?? ''));

        $name = strtolower($name);

        if ($name === 'admin') {
            throw ValidationException::withMessages([
                'name' => 'نام admin رزرو شده و فقط برای نقش مدیر سیستمی شرکت استفاده می‌شود.',
            ]);
        }

        if (! preg_match('/^[a-z][a-z0-9_-]{0,99}$/', $name)) {
            throw ValidationException::withMessages([
                'name' => 'نام سیستمی نقش باید با حرف انگلیسی کوچک شروع شود و فقط شامل حروف کوچک، عدد، خط تیره یا زیرخط باشد.',
            ]);
        }

        $data['name'] = $name;
        $data['is_system'] = false;

        $companyId = auth()->user()?->currentCompanyId();
        $parentRoleId = filled($data['parent_role_id'] ?? null) ? (int) $data['parent_role_id'] : null;
        $copyPermissionsFromRoleId = filled($data['copy_permissions_from_role_id'] ?? null)
            ? (int) $data['copy_permissions_from_role_id']
            : null;

        if (! $companyId) {
            throw ValidationException::withMessages([
                'name' => 'شرکت جاری معتبر نیست؛ ابتدا شرکت مجاز را انتخاب کنید.',
            ]);
        }

        if (Role::query()->where('company_id', $companyId)->where('name', $name)->exists()) {
            throw ValidationException::withMessages([
                'name' => 'این نام سیستمی قبلاً در شرکت جاری استفاده شده است.',
            ]);
        }

        foreach ([$parentRoleId, $copyPermissionsFromRoleId] as $roleId) {
            if ($roleId !== null && ! Role::query()
                ->where('company_id', $companyId)
                ->whereKey($roleId)
                ->exists()) {
                throw ValidationException::withMessages([
                    'parent_role_id' => 'نقش والد یا منبع کپی باید متعلق به شرکت جاری باشد.',
                ]);
            }
        }

        $this->companyId = (int) $companyId;
        $this->parentRoleId = $parentRoleId;
        $this->inheritPermissions = (bool) ($data['inherit_permissions'] ?? true);
        $this->copyPermissionsFromRoleId = $copyPermissionsFromRoleId;

        $data['company_id'] = $companyId;
        unset($data['parent_role_id'], $data['inherit_permissions'], $data['copy_permissions_from_role_id']);

        return $data;
    }

    protected function afterCreate(): void
    {
        app(UpdateRoleHierarchy::class)->handle(
            $this->record,
            $this->parentRoleId,
            $this->inheritPermissions,
            $this->copyPermissionsFromRoleId,
        );
    }

    public ?int $companyId = null;

    public ?int $parentRoleId = null;

    public bool $inheritPermissions = true;

    public ?int $copyPermissionsFromRoleId = null;
}
