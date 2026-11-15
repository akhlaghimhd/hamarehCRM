<?php

namespace App\Filament\Resources\RoleResource\Pages;

use App\Filament\Resources\RoleResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListRoles extends ListRecords
{
    protected static string $resource = RoleResource::class;

    protected static ?string $title = 'نقش‌ها و مجوزها';

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make()
                ->label('ایجاد نقش')
                ->visible(fn () => RoleResource::canCreate()),
        ];
    }
}
