<?php

namespace Tests\Feature;

use App\Filament\Resources\CompanyResource;
use App\Filament\Resources\RoleResource;
use App\Filament\Resources\UserResource;
use App\Models\Company;
use App\Models\Permission;
use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class FilamentCompanyAccessTest extends TestCase
{
    use RefreshDatabase;

    public function test_company_resource_only_exposes_the_current_company(): void
    {
        [$companyA, $companyB] = $this->createCompanies();

        $user = User::factory()->create();
        $user->companies()->attach($companyA->id, ['is_default' => true]);
        $user->companies()->attach($companyB->id, ['is_default' => false]);

        $this->actingAs($user);
        session(['current_company_id' => $companyA->id]);

        $companyIds = CompanyResource::getEloquentQuery()
            ->pluck('companies.id')
            ->all();

        $this->assertSame([$companyA->id], $companyIds);
        $this->assertNotContains($companyB->id, $companyIds);
    }

    public function test_company_resource_fails_closed_without_a_valid_company_context(): void
    {
        [$companyA] = $this->createCompanies();

        $user = User::factory()->create();
        $user->companies()->attach($companyA->id, ['is_default' => false]);

        $this->actingAs($user);
        session(['current_company_id' => null]);

        $this->assertSame([], CompanyResource::getEloquentQuery()->pluck('companies.id')->all());
    }

    public function test_user_resource_only_lists_members_of_the_current_company(): void
    {
        [$companyA, $companyB] = $this->createCompanies();

        $userA = User::factory()->create();
        $userB = User::factory()->create();
        $sharedUser = User::factory()->create();

        $userA->companies()->attach($companyA->id, ['is_default' => true]);
        $userB->companies()->attach($companyB->id, ['is_default' => true]);
        $sharedUser->companies()->attach($companyA->id, ['is_default' => true]);
        $sharedUser->companies()->attach($companyB->id, ['is_default' => false]);

        $this->actingAs($userA);
        session(['current_company_id' => $companyA->id]);

        $userIds = UserResource::getEloquentQuery()
            ->orderBy('users.id')
            ->pluck('users.id')
            ->all();

        $this->assertEqualsCanonicalizing(
            [$userA->id, $sharedUser->id],
            $userIds,
        );
        $this->assertNotContains($userB->id, $userIds);
    }

    public function test_user_resource_fails_closed_without_a_valid_company_context(): void
    {
        [$companyA] = $this->createCompanies();

        $user = User::factory()->create();
        $user->companies()->attach($companyA->id, ['is_default' => false]);

        $this->actingAs($user);
        session()->forget('current_company_id');

        $this->assertSame(
            [],
            UserResource::getEloquentQuery()->pluck('users.id')->all(),
        );
    }

    public function test_role_resource_only_lists_roles_of_the_current_company(): void
    {
        [$companyA, $companyB] = $this->createCompanies();

        $user = User::factory()->create();
        $user->companies()->attach($companyA->id, ['is_default' => true]);

        $roleA = $this->createRole($companyA, 'sales-a');
        $roleB = $this->createRole($companyB, 'sales-b');

        $this->actingAs($user);
        session(['current_company_id' => $companyA->id]);

        $roleIds = RoleResource::getEloquentQuery()
            ->orderBy('roles.id')
            ->pluck('roles.id')
            ->all();

        $this->assertSame([$roleA->id], $roleIds);
        $this->assertNotContains($roleB->id, $roleIds);
    }

    public function test_role_resource_fails_closed_for_a_non_member_company_selection(): void
    {
        [$companyA, $companyB] = $this->createCompanies();

        $user = User::factory()->create();
        $user->companies()->attach($companyA->id, ['is_default' => true]);

        $this->createRole($companyA, 'sales-a');
        $this->createRole($companyB, 'sales-b');

        $this->actingAs($user);
        session(['current_company_id' => $companyB->id]);

        $this->assertSame(
            [],
            RoleResource::getEloquentQuery()->pluck('roles.id')->all(),
        );
    }

    public function test_resource_permissions_are_denied_for_a_non_member_company_selection(): void
    {
        [$companyA, $companyB] = $this->createCompanies();

        $adminRole = $this->createRole($companyA, 'admin');
        $user = User::factory()->create();
        $user->companies()->attach($companyA->id, [
            'role_id' => $adminRole->id,
            'is_default' => true,
        ]);

        $this->actingAs($user);
        session(['current_company_id' => $companyA->id]);

        $this->assertTrue(UserResource::canViewAny());
        $this->assertTrue(RoleResource::canViewAny());

        session(['current_company_id' => $companyB->id]);

        $this->assertFalse(UserResource::canViewAny());
        $this->assertFalse(RoleResource::canViewAny());
    }

    public function test_role_bulk_delete_requires_company_role_management_permission(): void
    {
        [$companyA, $companyB] = $this->createCompanies();

        $salesRole = $this->createRole($companyA, 'sales');
        $user = User::factory()->create();
        $user->companies()->attach($companyA->id, [
            'role_id' => $salesRole->id,
            'is_default' => true,
        ]);

        $permission = Permission::query()->create([
            'name' => 'roles.manage',
            'label' => 'مدیریت نقش‌ها',
            'group' => 'system',
        ]);

        $this->actingAs($user);
        session(['current_company_id' => $companyA->id]);

        $this->assertFalse(RoleResource::canDeleteAny());

        $salesRole->permissions()->attach($permission->id);

        $this->assertTrue(RoleResource::canDeleteAny());

        session(['current_company_id' => $companyB->id]);

        $this->assertFalse(RoleResource::canDeleteAny());
    }

    /**
     * @return array{Company, Company}
     */
    private function createCompanies(): array
    {
        return [
            Company::query()->create([
                'name' => 'Company A',
                'slug' => 'company-a',
                'is_active' => true,
            ]),
            Company::query()->create([
                'name' => 'Company B',
                'slug' => 'company-b',
                'is_active' => true,
            ]),
        ];
    }

    private function createRole(Company $company, string $name): Role
    {
        return Role::query()->create([
            'company_id' => $company->id,
            'name' => $name,
            'label' => $name,
            'is_system' => false,
        ]);
    }
}
