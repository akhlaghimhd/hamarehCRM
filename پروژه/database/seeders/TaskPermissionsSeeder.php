<?php

namespace Database\Seeders;

use App\Models\Company;
use App\Models\Permission;
use App\Models\Role;
use Illuminate\Database\Seeder;

class TaskPermissionsSeeder extends Seeder
{
    public function run(): void
    {
        $permissions = [
            ['name' => 'tasks.view', 'label' => 'مشاهده کارهای من', 'group' => 'مدیریت تیم'],
            ['name' => 'tasks.create', 'label' => 'ایجاد کار', 'group' => 'مدیریت تیم'],
            ['name' => 'tasks.update', 'label' => 'ویرایش کارهای من', 'group' => 'مدیریت تیم'],
            ['name' => 'team.tasks.view', 'label' => 'مشاهده کارهای تیم', 'group' => 'مدیریت تیم'],
            ['name' => 'team.tasks.manage', 'label' => 'مدیریت کارهای تیم', 'group' => 'مدیریت تیم'],
            ['name' => 'team.tasks.assign', 'label' => 'ارجاع کار به تیم', 'group' => 'مدیریت تیم'],
        ];

        foreach ($permissions as $attributes) {
            Permission::query()->updateOrCreate(
                ['name' => $attributes['name']],
                $attributes,
            );
        }

        $defaultSalesPermissionIds = Permission::query()
            ->whereIn('name', ['tasks.view', 'tasks.create', 'tasks.update'])
            ->pluck('id');

        foreach (Company::query()->get() as $company) {
            $salesRole = Role::query()
                ->where('company_id', $company->id)
                ->where('name', 'sales')
                ->first();

            $salesRole?->permissions()->syncWithoutDetaching($defaultSalesPermissionIds);
        }
    }
}
