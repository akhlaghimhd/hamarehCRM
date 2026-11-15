<?php

namespace Database\Seeders;

use App\Models\Company;
use App\Models\Permission;
use App\Models\Role;
use Illuminate\Database\Seeder;

class SalesSeeder extends Seeder
{
    public function run(): void
    {
        $items = [
            ['name' => 'quotes.view', 'label' => 'مشاهده پیش‌فاکتور', 'group' => 'فروش'],
            ['name' => 'quotes.manage', 'label' => 'مدیریت پیش‌فاکتور', 'group' => 'فروش'],
            ['name' => 'orders.view', 'label' => 'مشاهده سفارش', 'group' => 'فروش'],
            ['name' => 'orders.manage', 'label' => 'مدیریت سفارش', 'group' => 'فروش'],
            ['name' => 'orders.confirm', 'label' => 'تأیید سفارش', 'group' => 'فروش'],
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

            $sales = Role::query()
                ->where('company_id', $company->id)
                ->where('name', 'sales')
                ->first();

            $admin = Role::query()
                ->where('company_id', $company->id)
                ->where('name', 'admin')
                ->first();

            if ($sales) {
                $sales->permissions()->syncWithoutDetaching($permIds);
            }

            if ($admin) {
                $admin->permissions()->sync(Permission::query()->pluck('id'));
            }
        }
    }
}
