<?php

namespace Database\Seeders;

use App\Models\Company;
use App\Models\Permission;
use App\Models\Role;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DemoAccessSeeder extends Seeder
{
    public function run(): void
    {
        $company = Company::query()->first();
        $sales = Role::query()->where('company_id', $company->id)->where('name', 'sales')->first();

        if ($sales) {
            $sales->permissions()->sync(
                Permission::query()->whereIn('name', [
                    'customers.view',
                    'customers.manage',
                ])->pluck('id')
            );
        }

        $user = User::query()->updateOrCreate(
            ['username' => 'sales'],
            [
                'name' => 'کاربر فروش',
                'email' => 'sales@myoffice.local',
                'password' => Hash::make('password'),
                'is_active' => true,
            ]
        );

        $user->companies()->syncWithoutDetaching([
            $company->id => [
                'role_id' => $sales->id,
                'is_default' => true,
            ],
        ]);
    }
}
