<?php
namespace App\Filament\Resources\PackagingTypeResource\Pages;
use App\Filament\Resources\PackagingTypeResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;
class EditPackagingType extends EditRecord {
    protected static string $resource = PackagingTypeResource::class;
    protected static ?string $title = 'ویرایش بسته‌بندی';
    protected function getHeaderActions(): array {
        return [DeleteAction::make()->label('حذف')->visible(fn ($r) => PackagingTypeResource::canDelete($r))];
    }
}
