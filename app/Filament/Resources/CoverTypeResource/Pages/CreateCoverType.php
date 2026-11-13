<?php
namespace App\Filament\Resources\CoverTypeResource\Pages;
use App\Filament\Resources\CoverTypeResource;
use App\Models\Company;
use Filament\Resources\Pages\CreateRecord;
class CreateCoverType extends CreateRecord {
    protected static string $resource = CoverTypeResource::class;
    protected static ?string $title = 'ایجاد نوع جلد';
    protected function mutateFormDataBeforeCreate(array $data): array {
        $data['company_id'] = session('current_company_id') ?? Company::query()->value('id');
        return $data;
    }
}
