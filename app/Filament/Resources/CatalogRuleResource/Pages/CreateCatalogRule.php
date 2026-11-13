<?php
namespace App\Filament\Resources\CatalogRuleResource\Pages;
use App\Filament\Resources\CatalogRuleResource;
use App\Models\Company;
use Filament\Resources\Pages\CreateRecord;
class CreateCatalogRule extends CreateRecord {
    protected static string $resource = CatalogRuleResource::class;
    protected static ?string $title = 'ایجاد قاعده';
    protected function mutateFormDataBeforeCreate(array $data): array {
        $data['company_id'] = session('current_company_id') ?? Company::query()->value('id');
        return $data;
    }
}
