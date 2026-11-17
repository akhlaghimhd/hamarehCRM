<?php

namespace App\Services;

use App\Models\ProductModel;
use InvalidArgumentException;

/**
 * قرارداد M05 برای M06 — اعتبارسنجی ترکیب مجاز و قیمت پایه.
 */
class CatalogConfigurator
{
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
            throw new InvalidArgumentException('مدل غیرفعال است: ' . $model->code);
        }

        if (($model->status ?? null) === 'draft' && ($selectionPayload['require_published'] ?? false)) {
            throw new InvalidArgumentException('مدل هنوز منتشر نشده: ' . $model->code);
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
            $allowed = $model->options()->pluck('product_options.id')->map(fn ($id) => (int) $id)->all();
            foreach ($optionIds as $oid) {
                if (! in_array((int) $oid, $allowed, true)) {
                    throw new InvalidArgumentException('قلم انتخابی در ماتریس مجاز این مدل نیست: ' . $oid);
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
                $m->id => trim(($m->code ? $m->code . ' — ' : '') . $m->name),
            ])
            ->all();
    }
}
