<?php

namespace Tests\Feature;

use App\Actions\Identity\CreateCompanyRole;
use App\Actions\Identity\CreateUserForCompany;
use App\Actions\Identity\UpdateCompanyRole;
use App\Actions\Identity\UpdateUserForCompany;
use App\Filament\Resources\CompanyResource;
use App\Filament\Resources\RoleResource;
use App\Filament\Resources\UserResource;
use App\Models\Company;
use App\Models\Permission;
use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class CompanyManagementDrawerActionsTest extends TestCase
{
    use RefreshDatabase;

    public function test_organization_resources_share_a_single_navigation_group(): void
    {
        $this->assertSame('مدیریت سازمان', CompanyResource::getNavigationGroup());
        $this->assertSame('مدیریت سازمان', UserResource::getNavigationGroup());
        $this->assertSame('مدیریت سازمان', RoleResource::getNavigationGroup());
    }

    public function test_company_create_view_and_edit_are_contextual_drawer_actions(): void
    {
        $this->assertSame(['index'], array_keys(CompanyResource::getPages()));
    }

    public function test_user_can_be_created_and_updated_through_company_scoped_actions(): void
    {
        [$company, $admin] = $this->companyAdmin('drawer-user');
        $staffRole = $this->role($company, 'staff');
        $this->actingAs($admin);
        session(['current_company_id' => $company->id]);

        $created = app(CreateUserForCompany::class)->handle([
            'name' => 'کاربر جدید',
            'username' => 'drawer-user-new',
            'email' => 'drawer-user@example.test',
            'password' => Hash::make('Secret123!'),
            'is_active' => true,
            'role_ids' => [$staffRole->id],
            'manager_user_id' => null,
        ], $admin);

        $this->assertDatabaseHas('company_user', [
            'company_id' => $company->id,
            'user_id' => $created->id,
            'role_id' => $staffRole->id,
        ]);

        $updated = app(UpdateUserForCompany::class)->handle($created, [
            'name' => 'کاربر ویرایش‌شده',
            'username' => 'drawer-user-updated',
            'email' => 'drawer-user-updated@example.test',
            'role_ids' => [$staffRole->id],
            'manager_user_id' => null,
            'is_active' => true,
        ], $admin);

        $this->assertSame('کاربر ویرایش‌شده', $updated->name);
        $this->assertSame('drawer-user-updated', $updated->username);
    }

    public function test_role_can_be_created_and_updated_through_company_scoped_actions(): void
    {
        [$company, $admin] = $this->companyAdmin('drawer-role');
        $permission = Permission::query()->create([
            'name' => 'customers.view',
            'label' => 'مشاهده مشتریان',
            'group' => 'مشتریان',
        ]);
        $this->actingAs($admin);
        session(['current_company_id' => $company->id]);

        $role = app(CreateCompanyRole::class)->handle([
            'name' => 'sales',
            'label' => 'فروش',
            'parent_role_id' => null,
            'inherit_permissions' => false,
            'copy_permissions_from_role_id' => null,
            'permissions' => [$permission->id],
        ], $admin);

        $this->assertTrue($role->permissions()->whereKey($permission->id)->exists());

        $updated = app(UpdateCompanyRole::class)->handle($role, [
            'name' => 'sales-team',
            'label' => 'تیم فروش',
            'parent_role_id' => null,
            'inherit_permissions' => false,
            'permissions' => [],
        ], $admin);

        $this->assertSame('sales-team', $updated->name);
        $this->assertSame('تیم فروش', $updated->label);
        $this->assertFalse($updated->permissions()->whereKey($permission->id)->exists());
    }

    private function companyAdmin(string $suffix): array
    {
        $company = Company::query()->create([
            'name' => 'شرکت '.$suffix,
            'slug' => 'company-'.$suffix,
            'is_active' => true,
        ]);
        $adminRole = Role::query()->create([
            'company_id' => $company->id,
            'name' => 'admin',
            'label' => 'مدیر شرکت',
            'is_system' => true,
            'inherit_permissions' => false,
        ]);
        $admin = User::factory()->create();
        $admin->companies()->attach($company->id, [
            'role_id' => $adminRole->id,
            'is_default' => true,
        ]);

        return [$company, $admin];
    }

    private function role(Company $company, string $name): Role
    {
        return Role::query()->create([
            'company_id' => $company->id,
            'name' => $name,
            'label' => ucfirst($name),
            'is_system' => false,
            'inherit_permissions' => false,
        ]);
    }
}
