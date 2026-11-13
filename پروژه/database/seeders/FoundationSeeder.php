<?php

namespace Database\Seeders;

use App\Models\Company;
use App\Models\Permission;
use App\Models\Role;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class FoundationSeeder extends Seeder
{
    public function run(): void
    {
        $permissions = [
            ['name' => 'users.view', 'label' => 'مشاهده کاربران', 'group' => 'کاربران'],
            ['name' => 'users.manage', 'label' => 'مدیریت کاربران', 'group' => 'کاربران'],
            ['name' => 'customers.view', 'label' => 'مشاهده مشتریان', 'group' => 'مشتریان'],
            ['name' => 'customers.manage', 'label' => 'مدیریت مشتریان', 'group' => 'مشتریان'],
            ['name' => 'companies.manage', 'label' => 'مدیریت شرکت‌ها', 'group' => 'سیستم'],
            ['name' => 'roles.manage', 'label' => 'مدیریت نقش‌ها', 'group' => 'سیستم'],
            ['name' => 'products.view', 'label' => 'مشاهده محصولات', 'group' => 'محصولات'],
            ['name' => 'products.manage', 'label' => 'مدیریت محصولات', 'group' => 'محصولات'],
            ['name' => 'opportunities.view', 'label' => 'مشاهده فرصت‌ها', 'group' => 'بازاریابی'],
            ['name' => 'opportunities.manage', 'label' => 'مدیریت فرصت‌ها', 'group' => 'بازاریابی'],
            ['name' => 'activities.view', 'label' => 'مشاهده فعالیت‌ها', 'group' => 'بازاریابی'],
            ['name' => 'activities.manage', 'label' => 'مدیریت فعالیت‌ها', 'group' => 'بازاریابی'],
        ];

        foreach ($permissions as $permission) {
            Permission::query()->updateOrCreate(
                ['name' => $permission['name']],
                $permission
            );
        }

        $companyA = Company::query()->updateOrCreate(
            ['slug' => 'myoffice'],
            [
                'name' => 'مای‌آفیس',
                'is_active' => true,
            ]
        );

        $companyB = Company::query()->updateOrCreate(
            ['slug' => 'demo-co'],
            [
                'name' => 'شرکت آزمایشی دوم',
                'is_active' => true,
            ]
        );

        foreach ([$companyA, $companyB] as $company) {
            $adminRole = Role::query()->updateOrCreate(
                ['company_id' => $company->id, 'name' => 'admin'],
                ['label' => 'مدیر', 'is_system' => true]
            );

            $salesRole = Role::query()->updateOrCreate(
                ['company_id' => $company->id, 'name' => 'sales'],
                ['label' => 'فروش', 'is_system' => true]
            );

            Role::query()->updateOrCreate(
                ['company_id' => $company->id, 'name' => 'support'],
                ['label' => 'پشتیبانی', 'is_system' => true]
            );

            $adminRole->permissions()->sync(Permission::query()->pluck('id'));

            $salesRole->permissions()->sync(
                Permission::query()->whereIn('name', [
                    'customers.view',
                    'customers.manage',
                    'products.view',
                    'opportunities.view',
                    'opportunities.manage',
                    'activities.view',
                    'activities.manage',
                ])->pluck('id')
            );
        }

        $admin = User::query()->updateOrCreate(
            ['username' => 'admin'],
            [
                'name' => 'مدیر سیستم',
                'email' => 'admin@myoffice.local',
                'password' => Hash::make('password'),
                'is_active' => true,
            ]
        );

        $sales = User::query()->updateOrCreate(
            ['username' => 'sales'],
            [
                'name' => 'کاربر فروش',
                'email' => 'sales@myoffice.local',
                'password' => Hash::make('password'),
                'is_active' => true,
            ]
        );

        $adminRoleA = Role::query()->where('company_id', $companyA->id)->where('name', 'admin')->first();
        $adminRoleB = Role::query()->where('company_id', $companyB->id)->where('name', 'admin')->first();
        $salesRoleA = Role::query()->where('company_id', $companyA->id)->where('name', 'sales')->first();

        $admin->companies()->sync([
            $companyA->id => ['role_id' => $adminRoleA->id, 'is_default' => true],
            $companyB->id => ['role_id' => $adminRoleB->id, 'is_default' => false],
        ]);

        $sales->companies()->sync([
            $companyA->id => ['role_id' => $salesRoleA->id, 'is_default' => true],
        ]);
    }
}
