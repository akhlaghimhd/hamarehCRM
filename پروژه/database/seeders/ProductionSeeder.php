<?php

namespace Database\Seeders;

use App\Models\Company;
use App\Models\Permission;
use App\Models\Role;
use Illuminate\Database\Seeder;

class ProductionSeeder extends Seeder
{
    public function run(): void
    {
        $items = [
            ['name' => 'work_packages.view', 'label' => 'مشاهده بسته کاری', 'group' => 'تولید'],
            ['name' => 'work_packages.manage', 'label' => 'مدیریت بسته کاری', 'group' => 'تولید'],
        ];

        foreach ($items as $item) {
            Permission::query()->updateOrCreate(
                ['name' => $item['name']],
                $item
            );
        }

        $permIds = Permission::query()
            ->whereIn('name', array_column($items, 'name'))
            ->pluck('id');

        foreach (Company::query()->get() as $company) {
            session(['current_company_id' => $company->id]);

            $admin = Role::query()
                ->where('company_id', $company->id)
                ->where('name', 'admin')
                ->first();

            if ($admin) {
                $admin->permissions()->syncWithoutDetaching($permIds);
            }
        }
    }
}
