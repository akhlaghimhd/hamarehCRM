<?php

namespace App\Filament\Resources\CoverSkuResource\Pages;

use App\Filament\Concerns\HasNotopiaBreadcrumbs;
use App\Filament\Resources\CoverSkuResource;
use App\Models\Company;
use App\Models\ProductLine;
use Filament\Resources\Pages\CreateRecord;

class CreateCoverSku extends CreateRecord
{
    use HasNotopiaBreadcrumbs;

    protected static string $resource = CoverSkuResource::class;

    protected static ?string $title = 'ایجاد جلد';

    public function getBreadcrumbs(): array
    {
        return $this->notopiaBreadcrumbs([
            CoverSkuResource::getUrl('index') => 'تعریف جلد',
            '' => 'ایجاد',
        ]);
    }

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $data['company_id'] = session('current_company_id') ?? Company::query()->value('id');
        $data['product_line_id'] = ProductLine::query()->where('code', 'notopia')->value('id');

        return $data;
    }
}
