<?php

namespace Database\Seeders;

use App\Models\CatalogSeason;
use App\Models\Company;
use App\Models\ProductFamily;
use Illuminate\Database\Seeder;

class CatalogArchitectureSeeder extends Seeder
{
    public function run(): void
    {
        $companyId = Company::query()->value('id');
        if (! $companyId) {
            return;
        }

        ProductFamily::query()->updateOrCreate(
            ['company_id' => $companyId, 'code' => 'KEYVAN'],
            ['name' => 'کیوان', 'sort' => 10, 'is_active' => true]
        );
        ProductFamily::query()->updateOrCreate(
            ['company_id' => $companyId, 'code' => 'MANA'],
            ['name' => 'مانا', 'sort' => 20, 'is_active' => true]
        );

        CatalogSeason::query()->updateOrCreate(
            ['company_id' => $companyId, 'code' => '1405'],
            ['name' => 'کالکشن ۱۴۰۵', 'year_label' => '1405', 'sort' => 10, 'is_active' => true]
        );
        CatalogSeason::query()->updateOrCreate(
            ['company_id' => $companyId, 'code' => '1406'],
            ['name' => 'کالکشن ۱۴۰۶', 'year_label' => '1406', 'sort' => 20, 'is_active' => true]
        );
    }
}
