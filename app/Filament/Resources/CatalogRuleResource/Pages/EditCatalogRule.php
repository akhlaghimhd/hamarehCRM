<?php
namespace App\Filament\Resources\CatalogRuleResource\Pages;
use App\Filament\Resources\CatalogRuleResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;
class EditCatalogRule extends EditRecord {
    protected static string $resource = CatalogRuleResource::class;
    protected static ?string $title = 'ویرایش قاعده';
    protected function getHeaderActions(): array {
        return [DeleteAction::make()->label('حذف')->visible(fn ($r) => CatalogRuleResource::canDelete($r))];
    }
}
