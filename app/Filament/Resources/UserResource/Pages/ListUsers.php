<?php

namespace App\Filament\Resources\UserResource\Pages;

use App\Filament\Resources\UserResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListUsers extends ListRecords
{
    protected static string $resource = UserResource::class;

    protected static ?string $title = 'کاربران';

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make()
                ->label('ایجاد کاربر')
                ->visible(fn () => UserResource::canCreate()),
        ];
    }
}
