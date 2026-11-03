<?php

namespace Database\Seeders;

use App\Models\Company;
use App\Models\CompanyUser;
use App\Models\Permission;
use App\Models\Role;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class FoundationSeeder extends Seeder
{
    public function run(): void
    {
        $this->call(PermissionSeeder::class);

        $company = Company::query()->updateOrCreate(
            ['code' => 'HAMAREH'],
            [
                'name' => 'هماره',
                'legal_name' => 'شرکت هماره',
                'is_active' => true,
            ]
        );

        $admin = User::query()->updateOrCreate(
            ['username' => 'admin'],
            [
                'name' => 'مدیر سیستم',
                'email' => 'admin@hamareh.local',
                'password' => Hash::make('ChangeMe@1405'),
                'is_active' => true,
            ]
        );

        $membership = CompanyUser::query()->updateOrCreate(
            [
                'company_id' => $company->id,
                'user_id' => $admin->id,
            ],
            ['is_default' => true]
        );

        $roleDefs = [
            'admin' => [
                'name' => 'مدیر',
                'permissions' => Permission::query()->pluck('code')->all(),
            ],
            'sales' => [
                'name' => 'فروش',
                'permissions' => [
                    'customers.view',
                    'customers.create',
                    'customers.update',
                ],
            ],
            'support' => [
                'name' => 'پشتیبانی',
                'permissions' => [
                    'customers.view',
                ],
            ],
        ];

        foreach ($roleDefs as $slug => $def) {
            $role = Role::query()->updateOrCreate(
                [
                    'company_id' => $company->id,
                    'slug' => $slug,
                ],
                [
                    'name' => $def['name'],
                    'is_system' => true,
                    'description' => 'نقش سیستمی اولیه',
                ]
            );

            $permissionIds = Permission::query()
                ->whereIn('code', $def['permissions'])
                ->pluck('id');

            $role->permissions()->sync($permissionIds);

            if ($slug === 'admin') {
                $membership->roles()->syncWithoutDetaching([$role->id]);
            }
        }
    }
}
