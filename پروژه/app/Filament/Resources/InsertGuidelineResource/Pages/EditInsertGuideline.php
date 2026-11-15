<?php
namespace App\Filament\Resources\InsertGuidelineResource\Pages;
use App\Filament\Resources\InsertGuidelineResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;
class EditInsertGuideline extends EditRecord {
    protected static string $resource = InsertGuidelineResource::class;
    protected static ?string $title = 'ویرایش راهنما';
    protected function getHeaderActions(): array {
        return [DeleteAction::make()->label('حذف')->visible(fn ($r) => InsertGuidelineResource::canDelete($r))];
    }
}
