<x-filament-panels::page>
    @php
        $line = $this->getNotopia();
        $items = [
            ['label' => 'مدل‌ها', 'desc' => 'کیوان، مانا و قیمت پایه', 'url' => \App\Filament\Resources\ProductModelResource::getUrl('index')],
            ['label' => 'سایزها', 'desc' => '۱/۸، ۱/۱۶، اروپایی', 'url' => \App\Filament\Resources\ProductSizeResource::getUrl('index')],
            ['label' => 'تعریف جلد', 'desc' => 'SKU + تنظیمات جنس/مدل/رنگ', 'url' => \App\Filament\Resources\CoverSkuResource::getUrl('index')],
            ['label' => 'اقلام و آپشن‌ها', 'desc' => 'کیف، NFC، نرم‌افزار', 'url' => \App\Filament\Resources\ProductOptionResource::getUrl('index')],
            ['label' => 'بسته‌بندی', 'desc' => 'جعبه محصول کیوان / مانا', 'url' => \App\Filament\Resources\PackagingTypeResource::getUrl('index')],
            ['label' => 'قواعد کاتالوگ', 'desc' => 'سازگاری و هشدارها', 'url' => \App\Filament\Resources\CatalogRuleResource::getUrl('index')],
            ['label' => 'راهنمای لت', 'desc' => 'دستورکار فروش و طراحی', 'url' => \App\Filament\Resources\InsertGuidelineResource::getUrl('index')],
        ];
    @endphp

    <div class="space-y-6" dir="rtl">
        <div class="rounded-2xl border border-gray-200 bg-white p-6 shadow-sm dark:border-gray-700 dark:bg-gray-900">
            <div class="text-right">
                <h2 class="text-xl font-bold text-gray-950 dark:text-white">نوتوپیا</h2>
                <p class="mt-1 text-sm leading-6 text-gray-600 dark:text-gray-400">
                    مدیریت مدل‌ها، سایزها، جلد، اقلام و قواعد مخصوص این محصول.
                </p>
                @if($line)
                    <p class="mt-2 text-xs text-gray-500">کد: {{ $line->code }} · {{ $line->lifecycle_status === 'active' ? 'فعال' : $line->lifecycle_status }}</p>
                @endif
            </div>
        </div>

        <div class="grid grid-cols-1 gap-4 sm:grid-cols-2 xl:grid-cols-4">
            @foreach($items as $item)
                <a href="{{ $item['url'] }}"
                   class="block rounded-2xl border border-gray-200 bg-white p-5 text-right shadow-sm transition
                          hover:border-primary-400 hover:shadow-md dark:border-gray-700 dark:bg-gray-900">
                    <div class="text-base font-semibold text-gray-950 dark:text-white">{{ $item['label'] }}</div>
                    <div class="mt-1 text-sm leading-6 text-gray-500 dark:text-gray-400">{{ $item['desc'] }}</div>
                </a>
            @endforeach
        </div>
    </div>
</x-filament-panels::page>
