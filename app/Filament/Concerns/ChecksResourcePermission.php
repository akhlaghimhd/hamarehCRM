<?php

namespace App\Filament\Concerns;

use Filament\Notifications\Notification;

trait ChecksResourcePermission
{
    public static function canViewAny(): bool
    {
        return static::allowed(static::$permissionView ?? null);
    }

    public static function shouldRegisterNavigation(): bool
    {
        return static::canViewAny();
    }

    public static function canCreate(): bool
    {
        return static::allowed(static::$permissionManage ?? static::$permissionView ?? null);
    }

    public static function canEdit($record): bool
    {
        return static::allowed(static::$permissionManage ?? static::$permissionView ?? null);
    }

    public static function canView($record): bool
    {
        return static::allowed(static::$permissionView ?? null);
    }

    public static function canDelete($record): bool
    {
        return static::allowed(static::$permissionManage ?? null);
    }

    public static function canDeleteAny(): bool
    {
        return static::allowed(static::$permissionManage ?? null);
    }

    protected static function allowed(?string $permission): bool
    {
        $user = auth()->user();

        if (! $user || ! ($user->is_active ?? true)) {
            return false;
        }

        if (method_exists($user, 'isCompanyAdmin') && $user->isCompanyAdmin()) {
            return true;
        }

        if (! $permission) {
            return false;
        }

        return method_exists($user, 'hasPermission') && $user->hasPermission($permission);
    }

    public static function deny(string $message = 'شما مجوز انجام این عملیات را ندارید.'): void
    {
        Notification::make()
            ->title('عدم دسترسی')
            ->body($message)
            ->danger()
            ->persistent()
            ->send();
    }
}
