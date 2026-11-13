<?php

namespace App\Filament\Resources\CoverStructureResource\Pages;

use App\Filament\Concerns\HasNotopiaBreadcrumbs;
use App\Filament\Resources\CoverStructureResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditCoverStructure extends EditRecord
{
    use HasNotopiaBreadcrumbs;

    protected static string $resource = CoverStructureResource::class;

    protected static ?string $title = 'ویرایش مدل ساخت';

    public function getBreadcrumbs(): array
    {
        return $this->coverSettingsBreadcrumbs('ویرایش مدل ساخت');
    }

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make()
                ->label('حذف')
                ->visible(fn ($record) => CoverStructureResource::canDelete($record)),
        ];
    }
}
