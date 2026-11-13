<?php

namespace Database\Seeders;

use App\Models\Company;
use App\Models\CoverSku;
use App\Models\Permission;
use App\Models\ProductModel;
use App\Models\ProductOption;
use App\Models\ProductSize;
use App\Models\Role;
use Illuminate\Database\Seeder;

class ProductCatalogSeeder extends Seeder
{
    public function run(): void
    {
        $company = Company::query()->first();
        if (! $company) {
            $this->command?->error('شرکتی وجود ندارد');
            return;
        }

        $companyId = $company->id;

        // مجوزها
        $perms = [
            ['name' => 'products.view', 'label' => 'مشاهده محصولات', 'group' => 'محصولات'],
            ['name' => 'products.manage', 'label' => 'مدیریت محصولات', 'group' => 'محصولات'],
        ];
        foreach ($perms as $p) {
            Permission::query()->updateOrCreate(['name' => $p['name']], $p);
        }

        // به نقش admin همه مجوزهای محصول
        $admin = Role::query()->where('company_id', $companyId)->where('name', 'admin')->first();
        if ($admin) {
            $admin->permissions()->syncWithoutDetaching(
                Permission::query()->whereIn('name', ['products.view', 'products.manage'])->pluck('id')
            );
        }

        // مدل‌ها: کیوان / مانا
        ProductModel::query()->updateOrCreate(
            ['company_id' => $companyId, 'code' => 'keivan'],
            [
                'name' => 'کیوان',
                'description' => 'کاغذ معمولی',
                'has_special_paper' => false,
                'includes_erasable_pen' => false,
                'includes_cloth' => false,
                'is_active' => true,
                'sort' => 1,
            ]
        );
        ProductModel::query()->updateOrCreate(
            ['company_id' => $companyId, 'code' => 'mana'],
            [
                'name' => 'مانا',
                'description' => 'کاغذ مخصوص + خودکار پاک‌شو + دستمال',
                'has_special_paper' => true,
                'includes_erasable_pen' => true,
                'includes_cloth' => true,
                'is_active' => true,
                'sort' => 2,
            ]
        );

        // سایزها
        $sizes = [
            ['code' => '1_8', 'name' => '۱/۸', 'cover_type_constraint' => 'cardboard', 'sort' => 1],
            ['code' => '1_16', 'name' => '۱/۱۶', 'cover_type_constraint' => 'cardboard', 'sort' => 2],
            ['code' => 'european', 'name' => 'اروپایی (رقعی)', 'cover_type_constraint' => 'leather', 'sort' => 3],
        ];
        foreach ($sizes as $s) {
            ProductSize::query()->updateOrCreate(
                ['company_id' => $companyId, 'code' => $s['code']],
                array_merge($s, ['is_active' => true])
            );
        }

        // چند SKU جلد نمونه
        $covers = [
            ['sku' => 'P-200', 'name' => 'جلد چرمی مشکی نرم', 'material' => 'leather_soft', 'color' => 'مشکی', 'structure' => 'one_piece'],
            ['sku' => 'P-201', 'name' => 'جلد چرمی قهوه‌ای سخت', 'material' => 'leather_hard', 'color' => 'قهوه‌ای', 'structure' => 'one_piece'],
            ['sku' => 'C-100', 'name' => 'جلد مقوایی ساده', 'material' => 'cardboard', 'color' => 'کرم', 'structure' => 'one_piece'],
        ];
        foreach ($covers as $c) {
            CoverSku::query()->updateOrCreate(
                ['company_id' => $companyId, 'sku' => $c['sku']],
                array_merge($c, ['stock_qty' => 0, 'is_active' => true])
            );
        }

        // آپشن‌ها
        $options = [
            ['code' => 'bag', 'name' => 'کیف', 'type' => 'addon', 'sort' => 1],
            ['code' => 'pen', 'name' => 'خودکار', 'type' => 'addon', 'sort' => 2],
            ['code' => 'nfc', 'name' => 'NFC', 'type' => 'nfc', 'sort' => 3],
            ['code' => 'packaging', 'name' => 'بسته‌بندی ویژه', 'type' => 'addon', 'sort' => 4],
            ['code' => 'logo_cover', 'name' => 'لوگو روی جلد', 'type' => 'branding', 'sort' => 5],
            ['code' => 'logo_bag', 'name' => 'لوگو روی کیف', 'type' => 'branding', 'sort' => 6],
            ['code' => 'software', 'name' => 'نرم‌افزار', 'type' => 'software', 'sort' => 7],
        ];
        foreach ($options as $o) {
            ProductOption::query()->updateOrCreate(
                ['company_id' => $companyId, 'code' => $o['code']],
                array_merge($o, ['is_active' => true])
            );
        }
    }
}
