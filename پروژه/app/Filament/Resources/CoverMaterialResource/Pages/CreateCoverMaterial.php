<?php

namespace App\Filament\Resources\CoverMaterialResource\Pages;

use App\Filament\Concerns\HasNotopiaBreadcrumbs;
use App\Filament\Resources\CoverMaterialResource;
use App\Models\Company;
use App\Models\ProductLine;
use Filament\Resources\Pages\CreateRecord;

class CreateCoverMaterial extends CreateRecord
{
    use HasNotopiaBreadcrumbs;

    protected static string $resource = CoverMaterialResource::class;

    protected static ?string $title = 'ایجاد جنس جلد';

    public function getBreadcrumbs(): array
    {
        return $this->coverSettingsBreadcrumbs('ایجاد جنس جلد');
    }

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $data['company_id'] = session('current_company_id') ?? Company::query()->value('id');
        $data['product_line_id'] = ProductLine::query()->where('code', 'notopia')->value('id');

        return $data;
    }
}
