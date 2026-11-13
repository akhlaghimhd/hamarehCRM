<?php

namespace App\Filament\Resources\ProductOptionResource\Pages;

use App\Filament\Concerns\HasNotopiaBreadcrumbs;
use App\Filament\Resources\ProductOptionResource;
use App\Models\AddonModelRule;
use App\Models\ProductModel;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;
use Illuminate\Support\Str;

class EditProductOption extends EditRecord
{
    use HasNotopiaBreadcrumbs;

    protected static string $resource = ProductOptionResource::class;

    protected static ?string $title = 'ویرایش قلم';

    public function getBreadcrumbs(): array
    {
        return $this->notopiaBreadcrumbs([
            ProductOptionResource::getUrl('index') => 'اقلام و آپشن‌ها',
            '' => $this->record->name ?? 'ویرایش',
        ]);
    }

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make()
                ->label('حذف قلم')
                ->visible(fn ($record) => ProductOptionResource::canDelete($record)),
        ];
    }

    protected function mutateFormDataBeforeFill(array $data): array
    {
        $data['allowed_model_ids'] = $this->record->modelRules()
            ->whereIn('availability', ['allowed', 'required'])
            ->pluck('product_model_id')
            ->all();

        return $data;
    }

    protected function mutateFormDataBeforeSave(array $data): array
    {
        unset($data['allowed_model_ids']);

        return $data;
    }

    protected function afterSave(): void
    {
        $state = $this->form->getState();
        $record = $this->record->fresh();
        $scope = $state['model_scope'] ?? $record->model_scope ?? 'all';
        $record->modelRules()->delete();

        $allModels = ProductModel::query()
            ->where('product_line_id', $record->product_line_id)
            ->pluck('id');

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
