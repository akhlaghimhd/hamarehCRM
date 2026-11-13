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
        ];

        foreach ($permissions as $permission) {
            Permission::query()->updateOrCreate(
                ['name' => $permission['name']],
                $permission
            );
        }

        $company = Company::query()->updateOrCreate(
            ['slug' => 'myoffice'],
            [
                'name' => 'مای‌آفیس',
                'is_active' => true,
            ]
        );

        $adminRole = Role::query()->updateOrCreate(
            ['company_id' => $company->id, 'name' => 'admin'],
            ['label' => 'مدیر', 'is_system' => true]
        );

        Role::query()->updateOrCreate(
            ['company_id' => $company->id, 'name' => 'sales'],
            ['label' => 'فروش', 'is_system' => true]
        );

        Role::query()->updateOrCreate(
            ['company_id' => $company->id, 'name' => 'support'],
            ['label' => 'پشتیبانی', 'is_system' => true]
        );

        $adminRole->permissions()->sync(Permission::query()->pluck('id'));

        $user = User::query()
            ->where('email', 'admin@myoffice.local')
            ->orWhere('username', 'admin')
            ->orWhere('email', 'admin@test.local')
            ->first();

        if ($user) {
            $user->update([
                'username' => $user->username ?: 'admin',
                'name' => $user->name ?: 'مدیر سیستم',
                'is_active' => true,
            ]);
        } else {
            $user = User::query()->create([
                'username' => 'admin',
                'name' => 'مدیر سیستم',
                'email' => 'admin@myoffice.local',
                'password' => Hash::make('password'),
                'is_active' => true,
            ]);
        }

        $company->users()->syncWithoutDetaching([
            $user->id => [
                'role_id' => $adminRole->id,
                'is_default' => true,
            ],
        ]);
    }
}
