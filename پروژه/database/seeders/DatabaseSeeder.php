<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $this->call([
            FoundationSeeder::class,
            DemoAccessSeeder::class,
            CatalogArchitectureSeeder::class,
            MasterProductsSeeder::class,
            NotopiaCatalogSeeder::class,
            CoverMasterSeeder::class,
            MarketingSeeder::class,
        ]);
    }
}
