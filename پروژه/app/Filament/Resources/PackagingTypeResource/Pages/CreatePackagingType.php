<?php
namespace App\Filament\Resources\PackagingTypeResource\Pages;
use App\Filament\Resources\PackagingTypeResource;
use App\Models\Company;
use Filament\Resources\Pages\CreateRecord;
class CreatePackagingType extends CreateRecord {
    protected static string $resource = PackagingTypeResource::class;
    protected static ?string $title = 'ایجاد بسته‌بندی';
    protected function mutateFormDataBeforeCreate(array $data): array {
        $data['company_id'] = session('current_company_id') ?? Company::query()->value('id');
        return $data;
    }
}
