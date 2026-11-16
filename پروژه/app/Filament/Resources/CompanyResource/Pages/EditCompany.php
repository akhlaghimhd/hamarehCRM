<?php

namespace App\Filament\Resources\CompanyResource\Pages;

use App\Actions\Identity\DeleteCompanyForUser;
use App\Actions\Identity\UpdateCompanyProfile;
use App\Filament\Pages\SwitchCompany;
use App\Filament\Resources\CompanyResource;
use App\Models\Company;
use App\Models\User;
use Filament\Actions\Action;
use Filament\Actions\ViewAction;
use Filament\Resources\Pages\EditRecord;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Database\Eloquent\Model;

class EditCompany extends EditRecord
{
    protected static string $resource = CompanyResource::class;

    protected static ?string $title = 'ویرایش شرکت';

    protected function handleRecordUpdate(Model $record, array $data): Model
    {
        $user = auth()->user();

        if (! $user instanceof User) {
            throw new AuthorizationException('برای ویرایش شرکت باید وارد حساب کاربری شوید.');
        }

        return app(UpdateCompanyProfile::class)->handle($record, $data, $user);
    }

    protected function getHeaderActions(): array
    {
        return [
            ViewAction::make()->label('مشاهده'),
            Action::make('deleteCompany')
                ->label('حذف نرم شرکت')
                ->icon('heroicon-o-trash')
                ->color('danger')
                ->requiresConfirmation()
                ->modalHeading('حذف نرم شرکت')
                ->modalDescription('شرکت از فهرست فعال خارج می‌شود. مدیران عضو همان شرکت می‌توانند آن را از صفحه تعویض شرکت بازیابی کنند.')
                ->visible(fn (): bool => (bool) (auth()->user()?->isCompanyAdmin() ?? false))
                ->action(function (Company $record, \Livewire\Component $livewire): void {
                    $user = auth()->user();

                    if (! $user instanceof User) {
                        throw new AuthorizationException('برای حذف شرکت باید وارد حساب کاربری شوید.');
                    }

                    app(DeleteCompanyForUser::class)->handle($record, $user);

                    $livewire->redirect(SwitchCompany::getUrl());
                }),
        ];
    }
}
