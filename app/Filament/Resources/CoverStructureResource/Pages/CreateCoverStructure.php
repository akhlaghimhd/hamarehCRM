<?php

namespace App\Filament\Resources\CoverStructureResource\Pages;

use App\Filament\Concerns\HasNotopiaBreadcrumbs;
use App\Filament\Resources\CoverStructureResource;
use App\Models\Company;
use App\Models\ProductLine;
use Filament\Resources\Pages\CreateRecord;

class CreateCoverStructure extends CreateRecord
{
    use HasNotopiaBreadcrumbs;

    protected static string $resource = CoverStructureResource::class;

    protected static ?string $title = 'ایجاد مدل ساخت';

    public function getBreadcrumbs(): array
    {
        return $this->coverSettingsBreadcrumbs('ایجاد مدل ساخت');
    }

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $data['company_id'] = session('current_company_id') ?? Company::query()->value('id');
        $data['product_line_id'] = ProductLine::query()->where('code', 'notopia')->value('id');

        return $data;
    }
}
