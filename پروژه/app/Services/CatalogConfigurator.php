<?php

namespace App\Services;

use App\Models\ProductModel;
use InvalidArgumentException;

/**
 * قرارداد M05 برای M06 — اعتبارسنجی ترکیب مجاز، قیمت پایه و Snapshot.
 */
class CatalogConfigurator
{
    /**
     * @return array{
     *     model_id: int|null,
     *     model_code: string|null,
     *     model_name: string|null,
     *     base_price: float,
     *     sizes: array<int, array{id:int,code:?string,name:string}>,
     *     cover_skus: array<int, array{id:int,code:?string,name:string}>,
     *     options: array<int, array{id:int,code:?string,name:string,min_qty:?int,max_qty:?int,is_recommended:bool}>
     * }
     */
    public function schema(int $companyId, ?int $modelId): array
    {
        if (! $modelId) {
            return [
                'model_id' => null,
                'model_code' => null,
                'model_name' => null,
                'base_price' => 0.0,
                'sizes' => [],
                'cover_skus' => [],
                'options' => [],
            ];
        }

        $model = ProductModel::query()
            ->where('company_id', $companyId)
            ->whereKey($modelId)
            ->with(['sizes', 'coverSkus', 'options'])
            ->first();

        if (! $model) {
            throw new InvalidArgumentException('مدل محصول در این شرکت یافت نشد.');
        }

        return [
            'model_id' => $model->id,
            'model_code' => $model->code,
            'model_name' => $model->name,
            'base_price' => (float) ($model->base_price ?? 0),
            'sizes' => $model->sizes->map(fn ($s) => [
                'id' => (int) $s->id,
                'code' => $s->code ?? null,
                'name' => (string) ($s->name ?? $s->code ?? $s->id),
            ])->values()->all(),
            'cover_skus' => $model->coverSkus->map(fn ($c) => [
                'id' => (int) $c->id,
                'code' => $c->code ?? null,
                'name' => (string) ($c->name ?? $c->code ?? $c->id),
            ])->values()->all(),
            'options' => $model->options->map(fn ($o) => [
                'id' => (int) $o->id,
                'code' => $o->code ?? null,
                'name' => (string) ($o->name ?? $o->code ?? $o->id),
                'min_qty' => isset($o->pivot->min_qty) ? (int) $o->pivot->min_qty : null,
                'max_qty' => isset($o->pivot->max_qty) ? (int) $o->pivot->max_qty : null,
                'is_recommended' => (bool) ($o->pivot->is_recommended ?? false),
            ])->values()->all(),
        ];
    }

    /**
     * @param  array<string, mixed>  $selectionPayload  keys: size_id?, cover_sku_id?, option_ids?, unit_price?, require_published?
     */
    public function validate(int $companyId, string $productType, ?int $modelId, array $selectionPayload = []): void
    {
        $allowedTypes = ['notopia', 'cheleva', 'service', 'other'];
        if ($productType === '' || ! in_array($productType, $allowedTypes, true)) {
            throw new InvalidArgumentException('نوع محصول مجاز نیست.');
        }

        if (in_array($productType, ['service', 'other'], true) && ! $modelId) {
            return;
        }

        if (! $modelId) {
            return;
        }

        $model = ProductModel::query()
            ->where('company_id', $companyId)
            ->whereKey($modelId)
            ->first();

        if (! $model) {
            throw new InvalidArgumentException('مدل محصول در این شرکت یافت نشد.');
        }

        if (! $model->is_active) {
            throw new InvalidArgumentException('مدل غیرفعال است: '.$model->code);
        }

        if (($model->status ?? null) === 'draft' && ($selectionPayload['require_published'] ?? false)) {
            throw new InvalidArgumentException('مدل هنوز منتشر نشده: '.$model->code);
        }

        $sizeId = isset($selectionPayload['size_id']) ? (int) $selectionPayload['size_id'] : null;
        if ($sizeId) {
            $ok = $model->sizes()->where('product_sizes.id', $sizeId)->exists();
            if (! $ok) {
                throw new InvalidArgumentException('سایز انتخابی در ماتریس مجاز این مدل نیست.');
            }
        }

        $coverSkuId = isset($selectionPayload['cover_sku_id']) ? (int) $selectionPayload['cover_sku_id'] : null;
        if ($coverSkuId) {
            $ok = $model->coverSkus()->where('cover_skus.id', $coverSkuId)->exists();
            if (! $ok) {
                throw new InvalidArgumentException('جلد انتخابی در ماتریس مجاز این مدل نیست.');
            }
        }

        $optionIds = $selectionPayload['option_ids'] ?? [];
        if (is_array($optionIds) && $optionIds !== []) {
            $allowed = $model->options()->get()->keyBy('id');
            foreach ($optionIds as $oid) {
                $id = (int) $oid;
                if (! $allowed->has($id)) {
                    throw new InvalidArgumentException('قلم انتخابی در ماتریس مجاز این مدل نیست: '.$id);
                }

                $opt = $allowed->get($id);
                $min = isset($opt->pivot->min_qty) ? (int) $opt->pivot->min_qty : null;
                $max = isset($opt->pivot->max_qty) ? (int) $opt->pivot->max_qty : null;
                $qty = isset($selectionPayload['option_qty'][$id])
                    ? (int) $selectionPayload['option_qty'][$id]
                    : 1;

                if ($min !== null && $qty < $min) {
                    throw new InvalidArgumentException('حداقل تعداد برای «'.($opt->name ?? $id).'» برابر '.$min.' است.');
                }
                if ($max !== null && $qty > $max) {
                    throw new InvalidArgumentException('حداکثر تعداد برای «'.($opt->name ?? $id).'» برابر '.$max.' است.');
                }
            }
        }
    }

    /**
     * @param  array<string, mixed>  $selectionPayload
     * @return array{unit_price: float, currency: string, breakdown: array<string, float>}
     */
    public function price(int $companyId, ?int $modelId, array $selectionPayload = []): array
    {
        $base = 0.0;
        $breakdown = [];

        if ($modelId) {
            $model = ProductModel::query()
                ->where('company_id', $companyId)
                ->whereKey($modelId)
                ->first();

            if ($model) {
                $base = (float) ($model->base_price ?? 0);
                $breakdown['base'] = $base;
            }
        }

        if (isset($selectionPayload['unit_price']) && $selectionPayload['unit_price'] !== null && $selectionPayload['unit_price'] !== '') {
            $base = (float) $selectionPayload['unit_price'];
            $breakdown['override'] = $base;
        }

        return [
            'unit_price' => $base,
            'currency' => 'IRR',
            'breakdown' => $breakdown ?: ['base' => $base],
        ];
    }

    /**
     * Snapshot تغییرناپذیر برای QuoteLine/OrderLine.
     *
     * @param  array<string, mixed>  $selectionPayload
     * @return array<string, mixed>
     */
    public function snapshot(int $companyId, string $productType, ?int $modelId, array $selectionPayload = []): array
    {
        $this->validate($companyId, $productType, $modelId, $selectionPayload);

        $schema = $this->schema($companyId, $modelId);
        $priced = $this->price($companyId, $modelId, $selectionPayload);

        $sizeId = isset($selectionPayload['size_id']) ? (int) $selectionPayload['size_id'] : null;
        $coverId = isset($selectionPayload['cover_sku_id']) ? (int) $selectionPayload['cover_sku_id'] : null;
        $optionIds = array_values(array_map('intval', $selectionPayload['option_ids'] ?? []));

        $size = collect($schema['sizes'])->firstWhere('id', $sizeId);
        $cover = collect($schema['cover_skus'])->firstWhere('id', $coverId);
        $options = collect($schema['options'])
            ->whereIn('id', $optionIds)
            ->values()
            ->all();

        return [
            'captured_at' => now()->toIso8601String(),
            'product_type' => $productType,
            'model' => [
                'id' => $schema['model_id'],
                'code' => $schema['model_code'],
                'name' => $schema['model_name'],
                'base_price' => $schema['base_price'],
            ],
            'size' => $size,
            'cover_sku' => $cover,
            'options' => $options,
            'price' => $priced,
        ];
    }

    /**
     * @return array<int, string>
     */
    public function modelOptions(int $companyId): array
    {
        return ProductModel::query()
            ->where('company_id', $companyId)
            ->where('is_active', true)
            ->orderBy('name')
            ->get()
            ->mapWithKeys(fn (ProductModel $m) => [
                $m->id => trim(($m->code ? $m->code.' — ' : '').$m->name),
            ])
            ->all();
    }
}
