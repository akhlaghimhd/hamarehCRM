<?php

namespace Database\Seeders;

use App\Models\Company;
use App\Models\CoverColor;
use App\Models\CoverMaterial;
use App\Models\CoverStructure;
use App\Models\ProductLine;
use Illuminate\Database\Seeder;

class CoverMasterSeeder extends Seeder
{
    public function run(): void
    {
        $company = Company::query()->first();
        $line = ProductLine::query()->where('code', 'notopia')->first();
        if (! $company || ! $line) {
            $this->command?->error('company/notopia missing');
            return;
        }
        $cid = $company->id;
        $lid = $line->id;

        $materials = [
            ['code' => 'leather', 'name' => 'چرم', 'sort' => 1],
            ['code' => 'thermo', 'name' => 'ترمو', 'sort' => 2],
            ['code' => 'cardboard', 'name' => 'مقوا', 'sort' => 3],
            ['code' => 'cellophane', 'name' => 'سلفون', 'sort' => 4],
            ['code' => 'multi_style', 'name' => 'مولتی‌استایل', 'sort' => 5],
            ['code' => 'plexi', 'name' => 'پلکسی', 'sort' => 6],
            ['code' => 'fabric', 'name' => 'پارچه', 'sort' => 7],
        ];
        foreach ($materials as $m) {
            CoverMaterial::query()->updateOrCreate(
                ['company_id' => $cid, 'code' => $m['code']],
                ['product_line_id' => $lid, 'name' => $m['name'], 'is_active' => true, 'sort' => $m['sort']]
            );
        }

        foreach ([
            ['code' => 'one_piece', 'name' => 'یک‌تکه', 'color_slots' => 1, 'sort' => 1],
            ['code' => 'two_piece', 'name' => 'دوتکه', 'color_slots' => 2, 'sort' => 2],
        ] as $s) {
            CoverStructure::query()->updateOrCreate(
                ['company_id' => $cid, 'code' => $s['code']],
                [
                    'product_line_id' => $lid,
                    'name' => $s['name'],
                    'color_slots' => $s['color_slots'],
                    'is_active' => true,
                    'sort' => $s['sort'],
                ]
            );
        }

        foreach ([
            ['name' => 'مشکی', 'hex' => '#1a1a1a', 'sort' => 1],
            ['name' => 'قهوه‌ای', 'hex' => '#6B3F2A', 'sort' => 2],
            ['name' => 'کرم', 'hex' => '#F5E6C8', 'sort' => 3],
            ['name' => 'آبی سرمه‌ای', 'hex' => '#1B2A4A', 'sort' => 4],
            ['name' => 'قرمز', 'hex' => '#8B1E1E', 'sort' => 5],
        ] as $c) {
            CoverColor::query()->updateOrCreate(
                ['company_id' => $cid, 'name' => $c['name']],
                ['product_line_id' => $lid, 'hex' => $c['hex'], 'is_active' => true, 'sort' => $c['sort']]
            );
        }

        $this->command?->info('Cover master data seeded');
    }
}
