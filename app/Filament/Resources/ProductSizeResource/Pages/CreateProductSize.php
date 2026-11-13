<?php
namespace App\Filament\Resources\ProductSizeResource\Pages;
use App\Filament\Resources\ProductSizeResource;
use App\Models\Company;
use Filament\Resources\Pages\CreateRecord;
class CreateProductSize extends CreateRecord {
    protected static string $resource = ProductSizeResource::class;
    protected static ?string $title = 'ایجاد سایز';
    protected function mutateFormDataBeforeCreate(array $data): array {
        $data['company_id'] = session('current_company_id') ?? Company::query()->value('id');
        return $data;
    }
}
