<?php
namespace App\Filament\Resources\InsertGuidelineResource\Pages;
use App\Filament\Resources\InsertGuidelineResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;
class ListInsertGuidelines extends ListRecords {
    protected static string $resource = InsertGuidelineResource::class;
    protected static ?string $title = 'راهنمای لت';
    protected function getHeaderActions(): array {
        return [CreateAction::make()->label('ایجاد راهنما')->visible(fn () => InsertGuidelineResource::canCreate())];
    }
}
