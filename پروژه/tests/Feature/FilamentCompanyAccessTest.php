<?php

namespace Tests\Feature;

use App\Filament\Resources\CompanyResource;
use App\Filament\Resources\CompanyResource\Pages\ListCompanies;
use App\Filament\Resources\RoleResource;
use App\Filament\Resources\UserResource;
use App\Models\Company;
use App\Models\Permission;
use App\Models\PlatformOwner;
use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class FilamentCompanyAccessTest extends TestCase
{
    use RefreshDatabase;


    public function test_only_platform_owner_without_memberships_can_access_company_creation(): void
    {
        $user = User::factory()->create();
        PlatformOwner::query()->create(['user_id' => $user->id]);

        $this->actingAs($user);
        session()->forget('current_company_id');

        $this->assertTrue(CompanyResource::canViewAny());
        $this->assertTrue(CompanyResource::canCreate());
    }

    public function test_platform_owner_company_list_shows_a_clear_empty_state_and_create_action(): void
    {
        $user = User::factory()->create();
        PlatformOwner::query()->create(['user_id' => $user->id]);

        $this->actingAs($user);
        session()->forget('current_company_id');

        Livewire::test(ListCompanies::class)
            ->assertSee('شرکتی برای نمایش وجود ندارد')
            ->assertSee('شرکت‌هایی که مجاز به مشاهده آن‌ها هستید در این فهرست نمایش داده می‌شوند.')
            ->assertSee('ایجاد شرکت');
    }

    public function test_non_admin_member_cannot_create_another_company(): void
    {
        [$company] = $this->createCompanies();
        $staffRole = $this->createRole($company, 'staff');
        $user = User::factory()->create();
        $user->companies()->attach($company->id, [
            'role_id' => $staffRole->id,
            'is_default' => true,
        ]);

        $this->actingAs($user);
        session(['current_company_id' => $company->id]);

        $this->assertFalse(CompanyResource::canCreate());
    }


    public function test_company_manager_can_manage_current_profile_but_cannot_create_companies(): void
    {
        [$companyA] = $this->createCompanies();
        $managerRole = $this->createRole($companyA, 'company-manager');
        $permission = Permission::query()->create([
            'name' => 'companies.manage',
            'label' => 'مدیریت شرکت‌ها',
            'group' => 'شرکت',
        ]);
        $managerRole->permissions()->attach($permission);
        $manager = User::factory()->create();
        $manager->companies()->attach($companyA->id, [
            'role_id' => $managerRole->id,
            'is_default' => true,
        ]);

        $this->actingAs($manager);
        session(['current_company_id' => $companyA->id]);

        $this->assertTrue(CompanyResource::canViewAny());
        $this->assertTrue(CompanyResource::canEdit($companyA));
        $this->assertFalse(CompanyResource::canCreate());
    }


    public function test_company_admin_cannot_access_another_company_record_directly(): void
    {
        [$companyA, $companyB] = $this->createCompanies();
        $adminRole = $this->createRole($companyA, 'admin');
        $adminRole->forceFill(['is_system' => true])->save();
        $user = User::factory()->create();
        $user->companies()->attach($companyA->id, [
            'role_id' => $adminRole->id,
            'is_default' => true,
        ]);

        $this->actingAs($user);
        session(['current_company_id' => $companyA->id]);

        $this->assertTrue(CompanyResource::canView($companyA));
        $this->assertTrue(CompanyResource::canEdit($companyA));
        $this->assertFalse(CompanyResource::canView($companyB));
        $this->assertFalse(CompanyResource::canEdit($companyB));
        $this->assertFalse(CompanyResource::canDelete($companyB));
    }

    public function test_company_resource_lists_all_companies_the_user_belongs_to(): void
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

        $this->assertEqualsCanonicalizing([$companyA->id, $companyB->id], $companyIds);
    }

    public function test_company_resource_lists_memberships_even_without_a_selected_company_context(): void
    {
        [$companyA] = $this->createCompanies();

        $user = User::factory()->create();
        $user->companies()->attach($companyA->id, ['is_default' => false]);

        $this->actingAs($user);
        session(['current_company_id' => null]);

        $this->assertSame([$companyA->id], CompanyResource::getEloquentQuery()->pluck('companies.id')->all());
    }


    public function test_non_admin_manager_cannot_edit_or_remove_an_administrator_account(): void
    {
        [$companyA, $companyB] = $this->createCompanies();
        $managerRole = $this->createRole($companyA, 'user-manager');
        $managerRole->permissions()->attach(Permission::query()->create([
            'name' => 'users.manage',
            'label' => 'مدیریت کاربران',
            'group' => 'کاربران',
        ]));
        $adminRole = $this->createRole($companyA, 'admin');
        $adminRole->forceFill(['is_system' => true])->save();

        $manager = User::factory()->create();
        $administrator = User::factory()->create();
        $manager->companies()->attach($companyA->id, [
            'role_id' => $managerRole->id,
            'is_default' => true,
        ]);
        $administrator->companies()->attach($companyA->id, [
            'role_id' => $adminRole->id,
            'is_default' => false,
        ]);
        $foreignUser = User::factory()->create();
        $foreignUser->companies()->attach($companyB->id, ['is_default' => true]);

        $this->actingAs($manager);
        session(['current_company_id' => $companyA->id]);

        $this->assertTrue(UserResource::canView($administrator));
        $this->assertFalse(UserResource::canEdit($administrator));
        $this->assertFalse(UserResource::canDelete($administrator));
        $this->assertFalse(UserResource::canView($foreignUser));
        $this->assertFalse(UserResource::canEdit($foreignUser));
        $this->assertFalse(UserResource::canDelete($foreignUser));
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


    public function test_company_admin_cannot_edit_or_delete_a_role_from_another_company_by_record_id(): void
    {
        [$companyA, $companyB] = $this->createCompanies();
        $adminRole = $this->createRole($companyA, 'admin');
        $adminRole->forceFill(['is_system' => true])->save();
        $foreignRole = $this->createRole($companyB, 'foreign-role');
        $user = User::factory()->create();
        $user->companies()->attach($companyA->id, [
            'role_id' => $adminRole->id,
            'is_default' => true,
        ]);

        $this->actingAs($user);
        session(['current_company_id' => $companyA->id]);

        $this->assertFalse(RoleResource::canView($foreignRole));
        $this->assertFalse(RoleResource::canEdit($foreignRole));
        $this->assertFalse(RoleResource::canDelete($foreignRole));
    }

    public function test_role_delete_permission_is_scoped_to_current_company(): void
    {
        [$companyA, $companyB] = $this->createCompanies();

        $salesRole = $this->createRole($companyA, 'sales');
        $adminRole = $this->createRole($companyA, 'admin');
        $adminRole->forceFill(['is_system' => true])->save();
        $staffRole = $this->createRole($companyB, 'staff');
        $user = User::factory()->create();
        $user->companies()->attach($companyA->id, [
            'role_id' => $adminRole->id,
            'is_default' => true,
        ]);
        $user->companies()->attach($companyB->id, [
            'role_id' => $staffRole->id,
            'is_default' => false,
        ]);

        $this->actingAs($user);
        session(['current_company_id' => $companyA->id]);

        $this->assertTrue(RoleResource::canDelete($salesRole));

        session(['current_company_id' => $companyB->id]);

        $this->assertFalse(RoleResource::canDelete($salesRole));
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
