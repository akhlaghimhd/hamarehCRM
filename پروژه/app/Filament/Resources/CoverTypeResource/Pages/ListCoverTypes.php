<?php
namespace App\Filament\Resources\CoverTypeResource\Pages;
use App\Filament\Resources\CoverTypeResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;
class ListCoverTypes extends ListRecords {
    protected static string $resource = CoverTypeResource::class;
    protected static ?string $title = 'انواع جلد';
    protected function getHeaderActions(): array {
        return [CreateAction::make()->label('ایجاد نوع جلد')->visible(fn () => CoverTypeResource::canCreate())];
    }
}
