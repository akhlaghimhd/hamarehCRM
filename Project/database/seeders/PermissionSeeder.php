<?php

namespace Database\Seeders;

use App\Models\Permission;
use Illuminate\Database\Seeder;

class PermissionSeeder extends Seeder
{
    public function run(): void
    {
        $items = [
            ['module' => 'companies', 'code' => 'companies.view', 'name' => 'مشاهده شرکت‌ها'],
            ['module' => 'companies', 'code' => 'companies.manage', 'name' => 'مدیریت شرکت‌ها'],
            ['module' => 'users', 'code' => 'users.view', 'name' => 'مشاهده کاربران'],
            ['module' => 'users', 'code' => 'users.manage', 'name' => 'مدیریت کاربران'],
            ['module' => 'roles', 'code' => 'roles.view', 'name' => 'مشاهده نقش‌ها'],
            ['module' => 'roles', 'code' => 'roles.manage', 'name' => 'مدیریت نقش‌ها'],
            ['module' => 'customers', 'code' => 'customers.view', 'name' => 'مشاهده مشتریان'],
            ['module' => 'customers', 'code' => 'customers.create', 'name' => 'ایجاد مشتری'],
            ['module' => 'customers', 'code' => 'customers.update', 'name' => 'ویرایش مشتری'],
            ['module' => 'customers', 'code' => 'customers.delete', 'name' => 'حذف مشتری'],
        ];

        foreach ($items as $item) {
            Permission::query()->updateOrCreate(
                ['code' => $item['code']],
                ['name' => $item['name'], 'module' => $item['module']]
            );
        }
    }
}
