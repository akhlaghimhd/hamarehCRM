<?php
namespace App\Filament\Resources\ProductSizeResource\Pages;
use App\Filament\Resources\ProductSizeResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;
class EditProductSize extends EditRecord {
    protected static string $resource = ProductSizeResource::class;
    protected static ?string $title = 'ویرایش سایز';
    protected function getHeaderActions(): array {
        return [DeleteAction::make()->label('حذف')->visible(fn ($r) => ProductSizeResource::canDelete($r))];
    }
}
