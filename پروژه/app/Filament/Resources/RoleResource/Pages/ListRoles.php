<?php

namespace App\Filament\Resources\RoleResource\Pages;

use App\Actions\Identity\CreateCompanyRole;
use App\Filament\Resources\RoleResource;
use App\Models\User;
use App\Models\Role;
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
                ->slideOver()
                ->using(function (array $data): Role {
                    $actor = auth()->user();
                    abort_unless($actor instanceof User, 403);

                    return app(CreateCompanyRole::class)->handle($data, $actor);
                })
                ->visible(fn () => RoleResource::canCreate()),
        ];
    }
}
