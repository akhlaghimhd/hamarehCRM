<?php

namespace Database\Seeders;

use App\Models\AddonModelRule;
use App\Models\CatalogRule;
use App\Models\Company;
use App\Models\CoverSku;
use App\Models\CoverType;
use App\Models\InsertGuideline;
use App\Models\PackagingType;
use App\Models\Permission;
use App\Models\ProductLine;
use App\Models\ProductModel;
use App\Models\ProductOption;
use App\Models\ProductSize;
use App\Models\Role;
use Illuminate\Database\Seeder;

class NotopiaCatalogSeeder extends Seeder
{
    public function run(): void
    {
        $company = Company::query()->first();
        if (! $company) {
            $this->command?->error('شرکت یافت نشد');
            return;
        }
        $cid = $company->id;

        foreach ([
            ['name' => 'products.view', 'label' => 'مشاهده محصولات', 'group' => 'محصولات'],
            ['name' => 'products.manage', 'label' => 'مدیریت محصولات', 'group' => 'محصولات'],
        ] as $p) {
            Permission::query()->updateOrCreate(['name' => $p['name']], $p);
        }
        $admin = Role::query()->where('company_id', $cid)->where('name', 'admin')->first();
        if ($admin) {
            $admin->permissions()->syncWithoutDetaching(
                Permission::query()->whereIn('name', ['products.view', 'products.manage'])->pluck('id')
            );
        }

        $line = ProductLine::query()->updateOrCreate(
            ['company_id' => $cid, 'code' => 'notopia'],
            ['name' => 'نوتوپیا', 'description' => 'نوشت‌افزار هوشمند', 'is_active' => true, 'sort' => 1]
        );

        $keivan = ProductModel::query()->updateOrCreate(
            ['company_id' => $cid, 'code' => 'keivan'],
            [
                'product_line_id' => $line->id,
                'name' => 'کیوان',
                'description' => 'کاغذ معمولی',
                'has_special_paper' => false,
                'includes_erasable_pen' => false,
                'includes_cloth' => false,
                'is_active' => true,
                'sort' => 1,
            ]
        );
        $mana = ProductModel::query()->updateOrCreate(
            ['company_id' => $cid, 'code' => 'mana'],
            [
                'product_line_id' => $line->id,
                'name' => 'مانا',
                'description' => 'کاغذ مخصوص + خودکار پاک‌شو + دستمال',
                'has_special_paper' => true,
                'includes_erasable_pen' => true,
                'includes_cloth' => true,
                'is_active' => true,
                'sort' => 2,
            ]
        );

        foreach ([
            ['code' => '1_8', 'name' => '۱/۸', 'sort' => 1, 'attributes' => ['cover_hint' => 'cardboard_family']],
            ['code' => '1_16', 'name' => '۱/۱۶', 'sort' => 2, 'attributes' => ['cover_hint' => 'cardboard_family']],
            ['code' => 'european', 'name' => 'اروپایی (رقعی)', 'sort' => 3, 'attributes' => ['cover_hint' => 'leather_family']],
        ] as $s) {
            ProductSize::query()->updateOrCreate(
                ['company_id' => $cid, 'code' => $s['code']],
                [
                    'product_line_id' => $line->id,
                    'name' => $s['name'],
                    'attributes' => $s['attributes'],
                    'is_active' => true,
                    'sort' => $s['sort'],
                ]
            );
        }

        $coverTypes = [
            ['code' => 'cardboard', 'name' => 'مقوایی', 'sort' => 1],
            ['code' => 'leather_soft', 'name' => 'چرمی نرم', 'sort' => 2],
            ['code' => 'leather_hard', 'name' => 'چرمی سخت', 'sort' => 3],
            ['code' => 'multi_style', 'name' => 'مولتی‌استایل', 'sort' => 4],
            ['code' => 'cellophane_print', 'name' => 'سلفونی چاپی', 'sort' => 5],
            ['code' => 'plexi', 'name' => 'پلکسی', 'sort' => 6],
        ];
        $coverTypeIds = [];
        foreach ($coverTypes as $ct) {
            $row = CoverType::query()->updateOrCreate(
                ['company_id' => $cid, 'code' => $ct['code']],
                [
                    'product_line_id' => $line->id,
                    'name' => $ct['name'],
                    'is_active' => true,
                    'sort' => $ct['sort'],
                ]
            );
            $coverTypeIds[$ct['code']] = $row->id;
        }

        CoverSku::query()->updateOrCreate(
            ['company_id' => $cid, 'sku' => 'S-P200'],
            [
                'product_line_id' => $line->id,
                'cover_type_id' => $coverTypeIds['leather_soft'],
                'series_type' => 'stock',
                'name' => 'جلد چرمی مشکی نرم',
                'material' => 'leather_soft',
                'color' => 'مشکی',
                'structure' => 'one_piece',
                'stock_qty' => 0,
                'is_active' => true,
            ]
        );
        CoverSku::query()->updateOrCreate(
            ['company_id' => $cid, 'sku' => 'C-CARD-001'],
            [
                'product_line_id' => $line->id,
                'cover_type_id' => $coverTypeIds['cardboard'],
                'series_type' => 'custom',
                'name' => 'جلد مقوایی سفارشی',
                'material' => 'cardboard',
                'color' => null,
                'structure' => 'one_piece',
                'stock_qty' => 0,
                'is_active' => true,
            ]
        );

        $packKeivanA = PackagingType::query()->updateOrCreate(
            ['company_id' => $cid, 'code' => 'keivan_box_a'],
            ['product_line_id' => $line->id, 'name' => 'جعبه کیوان نوع A', 'is_active' => true, 'sort' => 1]
        );
        $packKeivanB = PackagingType::query()->updateOrCreate(
            ['company_id' => $cid, 'code' => 'keivan_box_b'],
            ['product_line_id' => $line->id, 'name' => 'جعبه کیوان نوع B', 'is_active' => true, 'sort' => 2]
        );
        $packMana = PackagingType::query()->updateOrCreate(
            ['company_id' => $cid, 'code' => 'mana_box'],
            ['product_line_id' => $line->id, 'name' => 'جعبه مانا', 'is_active' => true, 'sort' => 3]
        );
        $packKeivanA->productModels()->syncWithoutDetaching([
            $keivan->id => ['availability' => 'allowed', 'is_preferred' => true],
        ]);
        $packKeivanB->productModels()->syncWithoutDetaching([
            $keivan->id => ['availability' => 'allowed', 'is_preferred' => false],
        ]);
        $packMana->productModels()->syncWithoutDetaching([
            $mana->id => ['availability' => 'required', 'is_preferred' => true],
        ]);

        $addons = [
            ['code' => 'pen_erasable', 'name' => 'خودکار پاک‌شو', 'type' => 'addon'],
            ['code' => 'cloth', 'name' => 'دستمال', 'type' => 'addon'],
            ['code' => 'bag', 'name' => 'کیف', 'type' => 'addon'],
            ['code' => 'nfc', 'name' => 'NFC', 'type' => 'nfc'],
            ['code' => 'software', 'name' => 'نرم‌افزار', 'type' => 'software'],
            ['code' => 'packaging_ship', 'name' => 'بسته‌بندی ارسال', 'type' => 'addon'],
        ];
        $addonIds = [];
        foreach ($addons as $i => $a) {
            $row = ProductOption::query()->updateOrCreate(
                ['company_id' => $cid, 'code' => $a['code']],
                [
                    'product_line_id' => $line->id,
                    'name' => $a['name'],
                    'type' => $a['type'],
                    'is_active' => true,
                    'sort' => $i + 1,
                ]
            );
            $addonIds[$a['code']] = $row->id;
        }

        // قواعد اقلام × مدل
        $rules = [
            ['pen_erasable', $mana->id, 'required', true, 'همراه مدل مانا'],
            ['pen_erasable', $keivan->id, 'forbidden', false, 'برای کیوان ارائه نمی‌شود'],
            ['cloth', $mana->id, 'required', true, 'همراه مدل مانا'],
            ['cloth', $keivan->id, 'forbidden', false, 'برای کیوان منطقی نیست'],
            ['bag', $mana->id, 'allowed', true, 'طراحی اصلی برای مانا'],
            ['bag', $keivan->id, 'allowed', false, 'در صورت درخواست مشتری'],
            ['nfc', $mana->id, 'allowed', false, null],
            ['nfc', $keivan->id, 'allowed', false, null],
            ['software', $mana->id, 'allowed', false, 'کد یکتا per جلد'],
            ['software', $keivan->id, 'allowed', false, 'کد یکتا per جلد'],
        ];
        foreach ($rules as [$code, $modelId, $availability, $preferred, $note]) {
            AddonModelRule::query()->updateOrCreate(
                [
                    'product_option_id' => $addonIds[$code],
                    'product_model_id' => $modelId,
                ],
                [
                    'availability' => $availability,
                    'is_preferred' => $preferred,
                    'note' => $note,
                ]
            );
        }

        CatalogRule::query()->updateOrCreate(
            ['company_id' => $cid, 'name' => 'جلد سخت برای ۱/۸ غیرمعمول'],
            [
                'product_line_id' => $line->id,
                'scope' => 'size_cover',
                'condition_json' => ['size_code' => '1_8', 'cover_type_code' => 'leather_hard'],
                'effect_json' => ['action' => 'warn'],
                'level' => 'discouraged',
                'message' => 'جلد چرمی سخت برای سایز ۱/۸ عرف نیست ولی در صورت درخواست ممکن است.',
                'is_active' => true,
            ]
        );

        InsertGuideline::query()->updateOrCreate(
            ['company_id' => $cid, 'title' => 'راهنمای لت تبلیغاتی نوتوپیا'],
            [
                'product_line_id' => $line->id,
                'body' => "لت تبلیغاتی کاملاً اختیاری است.\n- بودن/نبودن لت\n- تعداد برگ\n- طراحی توسط شرکت یا مشتری\nفروش و طراح باید این چارچوب را هنگام مذاکره و تولید رعایت کنند.",
                'audience' => 'both',
                'is_active' => true,
            ]
        );

        $this->command?->info('کاتالوگ نوتوپیا Seed شد.');
    }
}
