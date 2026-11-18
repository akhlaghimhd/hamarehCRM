<?php

namespace Database\Seeders;

use App\Models\Company;
use App\Models\Permission;
use App\Models\Role;
use Illuminate\Database\Seeder;

class FinanceSeeder extends Seeder
{
    public function run(): void
    {
        $items = [
            ['name' => 'invoices.view', 'label' => 'مشاهده فاکتور', 'group' => 'مالی'],
            ['name' => 'invoices.manage', 'label' => 'مدیریت فاکتور', 'group' => 'مالی'],
            ['name' => 'payments.view', 'label' => 'مشاهده پرداخت', 'group' => 'مالی'],
            ['name' => 'payments.manage', 'label' => 'ثبت پرداخت', 'group' => 'مالی'],
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
