<?php
namespace App\Filament\Resources\InsertGuidelineResource\Pages;
use App\Filament\Resources\InsertGuidelineResource;
use App\Models\Company;
use Filament\Resources\Pages\CreateRecord;
class CreateInsertGuideline extends CreateRecord {
    protected static string $resource = InsertGuidelineResource::class;
    protected static ?string $title = 'ایجاد راهنما';
    protected function mutateFormDataBeforeCreate(array $data): array {
        $data['company_id'] = session('current_company_id') ?? Company::query()->value('id');
        return $data;
    }
}
