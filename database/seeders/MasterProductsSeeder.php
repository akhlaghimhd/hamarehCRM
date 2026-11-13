<?php

namespace Database\Seeders;

use App\Models\Company;
use App\Models\ProductLine;
use Illuminate\Database\Seeder;

class MasterProductsSeeder extends Seeder
{
    public function run(): void
    {
        $company = Company::query()->first();
        if (! $company) {
            return;
        }
        $cid = $company->id;

        $items = [
            [
                'code' => 'notopia',
                'name' => 'نوتوپیا',
                'type' => 'configurable',
                'brand' => 'نوتوپیا',
                'short_description' => 'نوشت‌افزار هوشمند قابل پیکربندی',
                'description' => 'دفتر فیزیکی + نرم‌افزار اختیاری؛ مدل کیوان/مانا، سایز و جلد داینامیک',
                'sort' => 1,
            ],
            [
                'code' => 'jelva',
                'name' => 'جلوا',
                'type' => 'configurable',
                'brand' => 'جلوا',
                'short_description' => 'محصول جلوا',
                'description' => null,
                'sort' => 2,
            ],
            [
                'code' => 'bestar',
                'name' => 'بستار',
                'type' => 'configurable',
                'brand' => 'بستار',
                'short_description' => 'محصول بستار',
                'description' => null,
                'sort' => 3,
            ],
            [
                'code' => 'nemago',
                'name' => 'نماگو',
                'type' => 'configurable',
                'brand' => 'نماگو',
                'short_description' => 'محصول نماگو',
                'description' => null,
                'sort' => 4,
            ],
        ];

        foreach ($items as $item) {
            ProductLine::query()->updateOrCreate(
                ['company_id' => $cid, 'code' => $item['code']],
                array_merge($item, [
                    'lifecycle_status' => 'active',
                    'unit' => 'عدد',
                    'is_active' => true,
                ])
            );
        }
    }
}
