<?php

namespace App\Filament\Resources\CoverSkuResource\Pages;

use App\Filament\Concerns\HasNotopiaBreadcrumbs;
use App\Filament\Resources\CoverSkuResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditCoverSku extends EditRecord
{
    use HasNotopiaBreadcrumbs;

    protected static string $resource = CoverSkuResource::class;

    protected static ?string $title = 'ویرایش جلد';

    public function getBreadcrumbs(): array
    {
        return $this->notopiaBreadcrumbs([
            CoverSkuResource::getUrl('index') => 'تعریف جلد',
            '' => 'ویرایش',
        ]);
    }

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make()
                ->label('حذف')
                ->visible(fn ($record) => CoverSkuResource::canDelete($record)),
        ];
    }
}
