<?php

namespace App\Filament\Resources\CompanyResource\Pages;

use App\Actions\Identity\CreateCompanyForUser;
use App\Filament\Resources\CompanyResource;
use App\Models\Company;
use App\Models\User;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;
use Filament\Support\Enums\SlideOverPosition;

class ListCompanies extends ListRecords
{
    protected static string $resource = CompanyResource::class;

    protected static ?string $title = 'شرکت‌ها';

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make()
                ->label('ایجاد شرکت')
                ->slideOver()
                ->slideOverPosition(SlideOverPosition::Start)
                ->closeModalByClickingAway(false)
                ->closeModalByEscaping(false)
                ->modalCloseButton(false)
                ->modalCancelActionLabel('انصراف')
                ->modalSubmitActionLabel('ثبت شرکت')
                ->successNotificationTitle('شرکت با موفقیت ایجاد شد')
                ->using(function (array $data): Company {
                    $actor = auth()->user();
                    abort_unless($actor instanceof User, 403);

                    return app(CreateCompanyForUser::class)->handle($data, $actor);
                })
                ->visible(fn () => CompanyResource::canCreate()),
        ];
    }
}
