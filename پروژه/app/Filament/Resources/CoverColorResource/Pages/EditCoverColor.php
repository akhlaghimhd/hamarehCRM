<?php

namespace App\Filament\Resources\CoverColorResource\Pages;

use App\Filament\Concerns\HasNotopiaBreadcrumbs;
use App\Filament\Resources\CoverColorResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditCoverColor extends EditRecord
{
    use HasNotopiaBreadcrumbs;

    protected static string $resource = CoverColorResource::class;

    protected static ?string $title = 'ویرایش رنگ';

    public function getBreadcrumbs(): array
    {
        return $this->coverSettingsBreadcrumbs('ویرایش رنگ');
    }

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make()
                ->label('حذف')
                ->visible(fn ($record) => CoverColorResource::canDelete($record)),
        ];
    }
}
