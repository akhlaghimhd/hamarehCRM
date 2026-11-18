<?php

namespace App\Filament\Resources\WorkPackageResource\Pages;

use App\Filament\Resources\WorkPackageResource;
use Filament\Resources\Pages\ListRecords;

class ListWorkPackages extends ListRecords
{
    protected static string $resource = WorkPackageResource::class;

    protected static ?string $title = 'بسته‌های کاری';
}
