<?php
namespace App\Filament\Resources\ProductModelResource\Pages;
use App\Filament\Resources\ProductModelResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListProductModels extends ListRecords
{
    protected static string $resource = ProductModelResource::class;
    protected static ?string $title = 'مدل‌های محصول';
    protected function getHeaderActions(): array
    {
        return [CreateAction::make()->label('ایجاد مدل')->visible(fn () => ProductModelResource::canCreate())];
    }
}
