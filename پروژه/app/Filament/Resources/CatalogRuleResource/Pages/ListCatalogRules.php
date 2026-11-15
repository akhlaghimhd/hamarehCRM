<?php
namespace App\Filament\Resources\CatalogRuleResource\Pages;
use App\Filament\Resources\CatalogRuleResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;
class ListCatalogRules extends ListRecords {
    protected static string $resource = CatalogRuleResource::class;
    protected static ?string $title = 'قواعد کاتالوگ';
    protected function getHeaderActions(): array {
        return [CreateAction::make()->label('ایجاد قاعده')->visible(fn () => CatalogRuleResource::canCreate())];
    }
}
