<?php
namespace App\Filament\Resources\PackagingTypeResource\Pages;
use App\Filament\Resources\PackagingTypeResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;
class ListPackagingTypes extends ListRecords {
    protected static string $resource = PackagingTypeResource::class;
    protected static ?string $title = 'بسته‌بندی محصول';
    protected function getHeaderActions(): array {
        return [CreateAction::make()->label('ایجاد')->visible(fn () => PackagingTypeResource::canCreate())];
    }
}
