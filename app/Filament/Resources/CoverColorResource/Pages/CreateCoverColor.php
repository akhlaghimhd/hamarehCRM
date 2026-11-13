<?php

namespace App\Filament\Resources\CoverColorResource\Pages;

use App\Filament\Concerns\HasNotopiaBreadcrumbs;
use App\Filament\Resources\CoverColorResource;
use App\Models\Company;
use App\Models\ProductLine;
use Filament\Resources\Pages\CreateRecord;

class CreateCoverColor extends CreateRecord
{
    use HasNotopiaBreadcrumbs;

    protected static string $resource = CoverColorResource::class;

    protected static ?string $title = 'ایجاد رنگ';

    public function getBreadcrumbs(): array
    {
        return $this->coverSettingsBreadcrumbs('ایجاد رنگ');
    }

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $data['company_id'] = session('current_company_id') ?? Company::query()->value('id');
        $data['product_line_id'] = ProductLine::query()->where('code', 'notopia')->value('id');

        return $data;
    }
}
