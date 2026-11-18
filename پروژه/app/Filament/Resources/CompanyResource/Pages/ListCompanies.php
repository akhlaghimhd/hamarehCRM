<?php

namespace App\Filament\Resources\CompanyResource\Pages;

use App\Filament\Resources\CompanyResource;
use Filament\Actions\Action;
use Filament\Resources\Pages\ListRecords;
use Filament\Notifications\Notification;
use Illuminate\Validation\ValidationException;

class ListCompanies extends ListRecords
{
    protected static string $resource = CompanyResource::class;

    protected static ?string $title = 'شرکت‌ها';

    protected function onValidationError(ValidationException $exception): void
    {
        $message = collect($exception->errors())->flatten()->first();

        Notification::make()
            ->title('اطلاعات واردشده معتبر نیست')
            ->body($message ?: 'لطفاً فیلدهای مشخص‌شده را بررسی کنید.')
            ->danger()
            ->send();
    }

    protected function getHeaderActions(): array
    {
        return [
            Action::make('createCompany')
                ->label('ایجاد شرکت')
                ->icon('heroicon-o-plus')
                ->url(CompanyResource::getUrl('create'))
                ->visible(fn () => CompanyResource::canCreate()),
        ];
    }
}
