<?php

namespace App\\Actions\\Identity;

use App\\Models\\Company;
use App\\Models\\Permission;
use App\\Models\\Role;
use App\\Models\\User;
use Illuminate\\Support\\Facades\\DB;

class CreateCompanyForUser
{
    /**
     * Create a company and make its creator an administrator in one transaction.
     *
     * @param array<string, mixed> $attributes
     */
    public function handle(array $attributes, User $creator): Company
    {
        return DB::transaction(function () use ($attributes, $creator): Company {
            $company = Company::query()->create($attributes);

            $adminRole = Role::query()->create([
                'company_id' => $company->id,
                'name' => 'admin',
                'label' => 'مدیر شرکت',
                'is_system' => true,
                'inherit_permissions' => false,
            ]);

            $adminRole->permissions()->sync(Permission::query()->pluck('id'));

            $hasDefaultCompany = $creator->companies()
                ->wherePivot('is_default', true)
                ->where('companies.is_active', true)
                ->exists();

            $creator->companies()->attach($company->id, [
                'role_id' => $adminRole->id,
                'is_default' => ! $hasDefaultCompany,
            ]);

            $membershipId = DB::table('company_user')
                ->where('company_id', $company->id)
                ->where('user_id', $creator->id)
                ->value('id');

            DB::table('company_user_role')->insert([
                'company_user_id' => $membershipId,
                'role_id' => $adminRole->id,
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            session(['current_company_id' => $company->id]);

            return $company;
        });
    }
}
