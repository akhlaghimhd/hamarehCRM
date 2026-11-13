<?php

namespace App\Filament\Concerns;

use App\Filament\Pages\NotopiaCatalog;
use App\Filament\Resources\CoverSkuResource;
use App\Filament\Resources\ProductLineResource;

trait HasNotopiaBreadcrumbs
{
    /**
     * مسیر پایه: فهرست محصولات → نوتوپیا → ...
     *
     * @param  array<string, string|null>  $extra  [url => label] یا ['' => 'صفحه جاری']
     * @return array<string, string|null>
     */
    protected function notopiaBreadcrumbs(array $extra = []): array
    {
        $items = [];

        // سطح ۱: محصولات
        try {
            $items[ProductLineResource::getUrl('index')] = 'محصولات';
        } catch (\Throwable) {
            $items['/admin/product-lines'] = 'محصولات';
        }

        // سطح ۲: نمای کلی نوتوپیا (اگر روت موجود باشد)
        try {
            $items[NotopiaCatalog::getUrl()] = 'نوتوپیا';
        } catch (\Throwable) {
            $items['/admin/notopia-catalog'] = 'نوتوپیا';
        }

        foreach ($extra as $url => $label) {
            $items[$url] = $label;
        }

        return $items;
    }

    /**
     * مسیر مخصوص زیرشاخه جلد: ... → تعریف جلد → تنظیمات
     */
    protected function coverSettingsBreadcrumbs(string $currentLabel): array
    {
        try {
            $coverUrl = CoverSkuResource::getUrl('index');
        } catch (\Throwable) {
            $coverUrl = '/admin/cover-skus';
        }

        return $this->notopiaBreadcrumbs([
            $coverUrl => 'تعریف جلد',
            '' => $currentLabel, // صفحه جاری بدون لینک
        ]);
    }
}
