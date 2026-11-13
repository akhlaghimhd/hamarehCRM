<?php

namespace App\Services;

use InvalidArgumentException;

/**
 * قرارداد M05 برای M06 — اسکلت اولیه.
 * بعداً با allow-matrix نوتوپیا کامل می‌شود.
 */
class CatalogConfigurator
{
    /**
     * @param  array<string, mixed>  $selectionPayload
     */
    public function validate(int $companyId, string $productType, ?int $modelId, array $selectionPayload = []): void
    {
        if ($productType === '') {
            throw new InvalidArgumentException('نوع محصول مشخص نیست.');
        }

        $allowed = ['notopia', 'cheleva', 'service', 'other'];
        if (! in_array($productType, $allowed, true)) {
            throw new InvalidArgumentException('نوع محصول مجاز نیست: ' . $productType);
        }
    }

    /**
     * @param  array<string, mixed>  $selectionPayload
     * @return array{unit_price: float, currency: string, breakdown: array<string, float>}
     */
    public function price(int $companyId, ?int $modelId, array $selectionPayload = []): array
    {
        $unit = (float) ($selectionPayload['unit_price'] ?? 0);

        return [
            'unit_price' => $unit,
            'currency' => 'IRR',
            'breakdown' => [
                'base' => $unit,
            ],
        ];
    }
}
