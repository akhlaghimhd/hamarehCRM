<?php

namespace App\Filament\Resources\ProductLineResource\Pages;

use App\Filament\Resources\ProductLineResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditProductLine extends EditRecord
{
    protected static string $resource = ProductLineResource::class;

    protected static ?string $title = 'ویرایش محصول';

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make()
                ->label('حذف')
                ->visible(fn ($record) => ProductLineResource::canDelete($record)),
        ];
    }
}
