<?php

namespace App\Filament\Resources\CoverMaterialResource\Pages;

use App\Filament\Concerns\HasNotopiaBreadcrumbs;
use App\Filament\Resources\CoverMaterialResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditCoverMaterial extends EditRecord
{
    use HasNotopiaBreadcrumbs;

    protected static string $resource = CoverMaterialResource::class;

    protected static ?string $title = 'ویرایش جنس جلد';

    public function getBreadcrumbs(): array
    {
        return $this->coverSettingsBreadcrumbs('ویرایش جنس جلد');
    }

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make()
                ->label('حذف')
                ->visible(fn ($record) => CoverMaterialResource::canDelete($record)),
        ];
    }
}
