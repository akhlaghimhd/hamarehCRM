<?php

namespace Database\Seeders;

use App\Models\Company;
use App\Models\Permission;
use App\Models\Role;
use Illuminate\Database\Seeder;

class SalesNetworkSeeder extends Seeder
{
    public function run(): void
    {
        $items = [
            ['name' => 'sales_network.view', 'label' => 'مشاهده شبکه فروش', 'group' => 'شبکه فروش'],
            ['name' => 'sales_network.manage', 'label' => 'مدیریت شبکه فروش', 'group' => 'شبکه فروش'],
        ];

        foreach ($items as $item) {
            Permission::query()->updateOrCreate(['name' => $item['name']], $item);
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
