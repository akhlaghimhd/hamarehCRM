<?php

namespace App\Filament\Resources\ProductOptionResource\Pages;

use App\Filament\Concerns\HasNotopiaBreadcrumbs;
use App\Filament\Resources\ProductOptionResource;
use App\Models\AddonModelRule;
use App\Models\Company;
use App\Models\ProductLine;
use App\Models\ProductModel;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Support\Str;

class CreateProductOption extends CreateRecord
{
    use HasNotopiaBreadcrumbs;

    protected static string $resource = ProductOptionResource::class;

    protected static ?string $title = 'قلم جدید';

    public function getBreadcrumbs(): array
    {
        return $this->notopiaBreadcrumbs([
            ProductOptionResource::getUrl('index') => 'اقلام و آپشن‌ها',
            '' => 'ایجاد',
        ]);
    }

    protected function getRedirectUrl(): string
    {
        return ProductOptionResource::getUrl('edit', ['record' => $this->record]);
    }

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $data['company_id'] = session('current_company_id') ?? Company::query()->value('id');
        $data['product_line_id'] = ProductLine::query()->where('code', 'notopia')->value('id');
        unset($data['allowed_model_ids']);

        return $data;
    }

    protected function afterCreate(): void
    {
        $state = $this->form->getState();
        $record = $this->record;
        $scope = $state['model_scope'] ?? 'all';
        $lineId = $record->product_line_id;
        $allModels = ProductModel::query()->where('product_line_id', $lineId)->pluck('id');

        if ($scope === 'all') {
            foreach ($allModels as $modelId) {
                AddonModelRule::query()->create([
                    'product_option_id' => $record->id,
                    'product_model_id' => $modelId,
                    'availability' => 'allowed',
                    'is_preferred' => false,
                ]);
            }
        } else {
            $allowed = collect($state['allowed_model_ids'] ?? [])->map(fn ($id) => (int) $id);
            foreach ($allModels as $modelId) {
                AddonModelRule::query()->create([
                    'product_option_id' => $record->id,
                    'product_model_id' => $modelId,
                    'availability' => $allowed->contains((int) $modelId) ? 'allowed' : 'forbidden',
                    'is_preferred' => false,
                ]);
            }
        }

        foreach ($record->customAttributes as $attr) {
            if (! $attr->key) {
                $attr->key = Str::slug($attr->label ?: 'field', '_');
                $attr->save();
            }
        }
    }
}
