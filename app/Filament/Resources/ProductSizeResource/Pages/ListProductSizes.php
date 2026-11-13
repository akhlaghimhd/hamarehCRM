<?php
namespace App\Filament\Resources\ProductSizeResource\Pages;
use App\Filament\Resources\ProductSizeResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;
class ListProductSizes extends ListRecords {
    protected static string $resource = ProductSizeResource::class;
    protected static ?string $title = 'سایزها';
    protected function getHeaderActions(): array {
        return [CreateAction::make()->label('ایجاد سایز')->visible(fn () => ProductSizeResource::canCreate())];
    }
}
