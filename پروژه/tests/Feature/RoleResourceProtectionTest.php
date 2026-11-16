<?php

namespace Tests\Feature;

use App\Filament\Resources\RoleResource;
use App\Filament\Resources\RoleResource\Pages\CreateRole;
use App\Filament\Resources\RoleResource\Pages\EditRole;
use App\Actions\Identity\SyncCompanyRolePermissions;
use App\Actions\Identity\UpdateRoleHierarchy;
use App\Models\Company;
use App\Models\Permission;
use App\Models\Role;
use App\Models\User;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Validation\ValidationException;
use Livewire\Livewire;
use Tests\TestCase;

class RoleResourceProtectionTest extends TestCase
{
    use RefreshDatabase;

    public function test_custom_role_cannot_be_created_with_reserved_admin_name(): void
    {
        [$company, $admin] = $this->companyAdmin('reserved-create');

        $this->actingAs($admin);
        session(['current_company_id' => $company->id]);

        Livewire::test(CreateRole::class)
            ->fillForm([
                'name' => 'admin',
                'label' => 'مدیر جعلی',
            ])
            ->call('create')
            ->assertHasFormErrors(['name']);

        $this->assertSame(
            1,
            Role::query()->where('company_id', $company->id)->where('name', 'admin')->count(),
        );
    }

    public function test_custom_role_cannot_be_renamed_to_admin(): void
    {
        [$company, $admin] = $this->companyAdmin('reserved-edit');
        $role = Role::query()->create([
            'company_id' => $company->id,
            'name' => 'sales',
            'label' => 'فروش',
            'is_system' => false,
            'inherit_permissions' => false,
        ]);

        $this->actingAs($admin);
        session(['current_company_id' => $company->id]);

        Livewire::test(EditRole::class, ['record' => $role->getRouteKey()])
            ->fillForm([
                'name' => 'admin',
                'label' => 'مدیر جعلی',
                'parent_role_id' => null,
                'inherit_permissions' => false,
                'permissions' => [],
            ])
            ->call('save')
            ->assertHasFormErrors(['name']);

        $this->assertDatabaseHas('roles', [
            'id' => $role->id,
            'name' => 'sales',
            'is_system' => false,
        ]);
    }





    public function test_system_admin_role_cannot_be_selected_as_parent(): void
    {
        [$company, $admin] = $this->companyAdmin('admin-parent');
        $adminRole = Role::query()
            ->where('company_id', $company->id)
            ->where('name', 'admin')
            ->firstOrFail();
        $customRole = Role::query()->create([
            'company_id' => $company->id,
            'name' => 'operations',
            'label' => 'عملیات',
            'is_system' => false,
            'inherit_permissions' => true,
        ]);

        $this->actingAs($admin);
        session(['current_company_id' => $company->id]);

        $this->expectException(ValidationException::class);
        app(UpdateRoleHierarchy::class)->handle(
            $customRole,
            $adminRole->id,
            true,
            null,
            $admin,
        );
    }


    public function test_legacy_admin_parent_does_not_leak_central_permissions_to_custom_role(): void
    {
        [$company] = $this->companyAdmin('legacy-admin-parent');
        $adminRole = Role::query()
            ->where('company_id', $company->id)
            ->where('name', 'admin')
            ->firstOrFail();
        $systemPermission = Permission::query()->create([
            'name' => 'companies.manage',
            'label' => 'مدیریت شرکت‌ها',
            'group' => 'سیستم',
        ]);
        $ordinaryPermission = Permission::query()->create([
            'name' => 'orders.view',
            'label' => 'مشاهده سفارش‌ها',
            'group' => 'فروش',
        ]);
        $adminRole->permissions()->attach([$systemPermission->id, $ordinaryPermission->id]);

        $customRole = Role::query()->create([
            'company_id' => $company->id,
            'name' => 'legacy-child',
            'label' => 'نقش فرزند قدیمی',
            'parent_role_id' => $adminRole->id,
            'inherit_permissions' => true,
            'is_system' => false,
        ]);

        $effectiveIds = $customRole->effectivePermissionIds();

        $this->assertTrue($effectiveIds->contains($ordinaryPermission->id));
        $this->assertFalse($effectiveIds->contains($systemPermission->id));

        $user = User::factory()->create();
        $user->companies()->attach($company->id, [
            'role_id' => $customRole->id,
            'is_default' => true,
        ]);
        $this->actingAs($user);
        session(['current_company_id' => $company->id]);

        $this->assertFalse($user->hasPermission('companies.manage'));
        $this->assertTrue($user->hasPermission('orders.view'));
    }

    public function test_role_manager_cannot_edit_or_change_system_admin_role(): void
    {
        $company = Company::query()->create([
            'name' => 'شرکت مدیر نقش',
            'slug' => 'role-manager-company',
            'is_active' => true,
        ]);
        $adminRole = Role::query()->create([
            'company_id' => $company->id,
            'name' => 'admin',
            'label' => 'مدیر شرکت',
            'is_system' => true,
            'inherit_permissions' => false,
        ]);
        $managerRole = Role::query()->create([
            'company_id' => $company->id,
            'name' => 'role-manager',
            'label' => 'مدیر نقش',
            'is_system' => false,
            'inherit_permissions' => false,
        ]);
        $rolesManage = Permission::query()->create([
            'name' => 'roles.manage',
            'label' => 'مدیریت نقش‌ها',
            'group' => 'سیستم',
        ]);
        $managerRole->permissions()->attach($rolesManage);

        $manager = User::factory()->create();
        $manager->companies()->attach($company->id, [
            'role_id' => $managerRole->id,
            'is_default' => true,
        ]);

        $this->actingAs($manager);
        session(['current_company_id' => $company->id]);

        $this->assertFalse(RoleResource::canEdit($adminRole));
        $this->assertTrue(RoleResource::canEdit($managerRole));
        $this->assertFalse(RoleResource::canDelete($adminRole));

        $this->expectException(AuthorizationException::class);
        app(SyncCompanyRolePermissions::class)->handle($adminRole, [], $manager);
    }

    public function test_central_permissions_can_only_be_synced_to_system_admin_role(): void
    {
        [$company, $admin] = $this->companyAdmin('central-permission-sync');
        $systemPermission = Permission::query()->create([
            'name' => 'roles.manage',
            'label' => 'مدیریت نقش‌ها',
            'group' => 'سیستم',
        ]);
        $customRole = Role::query()->create([
            'company_id' => $company->id,
            'name' => 'operations',
            'label' => 'عملیات',
            'is_system' => false,
            'inherit_permissions' => false,
        ]);
        $salesSystemRole = Role::query()->create([
            'company_id' => $company->id,
            'name' => 'sales',
            'label' => 'فروش',
            'is_system' => true,
            'inherit_permissions' => false,
        ]);
        $adminRole = Role::query()
            ->where('company_id', $company->id)
            ->where('name', 'admin')
            ->firstOrFail();

        $this->actingAs($admin);
        session(['current_company_id' => $company->id]);

        app(SyncCompanyRolePermissions::class)->handle($customRole, [$systemPermission->id], $admin);
        app(SyncCompanyRolePermissions::class)->handle($salesSystemRole, [$systemPermission->id], $admin);
        app(SyncCompanyRolePermissions::class)->handle($adminRole, [$systemPermission->id], $admin);

        $this->assertFalse($customRole->permissions()->whereKey($systemPermission->id)->exists());
        $this->assertFalse($salesSystemRole->permissions()->whereKey($systemPermission->id)->exists());
        $this->assertTrue($adminRole->permissions()->whereKey($systemPermission->id)->exists());
    }

    public function test_copying_admin_permissions_does_not_grant_central_permissions_to_custom_roles(): void
    {
        [$company, $admin] = $this->companyAdmin('central-permission-copy');
        $adminRole = Role::query()
            ->where('company_id', $company->id)
            ->where('name', 'admin')
            ->firstOrFail();

        $systemPermission = Permission::query()->create([
            'name' => 'companies.manage',
            'label' => 'مدیریت شرکت‌ها',
            'group' => 'سیستم',
        ]);
        $ordinaryPermission = Permission::query()->create([
            'name' => 'customers.view',
            'label' => 'مشاهده مشتریان',
            'group' => 'مشتریان',
        ]);
        $adminRole->permissions()->attach([$systemPermission->id, $ordinaryPermission->id]);

        $customRole = Role::query()->create([
            'company_id' => $company->id,
            'name' => 'operations',
            'label' => 'عملیات',
            'is_system' => false,
            'inherit_permissions' => false,
        ]);

        $this->actingAs($admin);
        session(['current_company_id' => $company->id]);

        app(UpdateRoleHierarchy::class)->handle(
            $customRole,
            null,
            false,
            $adminRole->id,
            $admin,
        );

        $this->assertTrue($customRole->permissions()->whereKey($ordinaryPermission->id)->exists());
        $this->assertFalse($customRole->permissions()->whereKey($systemPermission->id)->exists());
    }

    /**
     * @return array{Company, User}
     */
    private function companyAdmin(string $suffix): array
    {
        $company = Company::query()->create([
            'name' => 'شرکت '.$suffix,
            'slug' => 'company-'.$suffix,
            'is_active' => true,
        ]);
        $role = Role::query()->create([
            'company_id' => $company->id,
            'name' => 'admin',
            'label' => 'مدیر شرکت',
            'is_system' => true,
            'inherit_permissions' => false,
        ]);
        $user = User::factory()->create();
        $user->companies()->attach($company->id, [
            'role_id' => $role->id,
            'is_default' => true,
        ]);

        return [$company, $user];
    }
}
