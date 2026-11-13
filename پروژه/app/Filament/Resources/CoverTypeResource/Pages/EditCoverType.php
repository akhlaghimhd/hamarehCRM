<?php
namespace App\Filament\Resources\CoverTypeResource\Pages;
use App\Filament\Resources\CoverTypeResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;
class EditCoverType extends EditRecord {
    protected static string $resource = CoverTypeResource::class;
    protected static ?string $title = 'ویرایش نوع جلد';
    protected function getHeaderActions(): array {
        return [DeleteAction::make()->label('حذف')->visible(fn ($r) => CoverTypeResource::canDelete($r))];
    }
}
