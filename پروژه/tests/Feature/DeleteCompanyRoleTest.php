<?php

namespace Tests\Feature;

use App\Actions\Identity\DeleteCompanyRole;
use App\Models\Company;
use App\Models\Permission;
use App\Models\Role;
use App\Models\User;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

class DeleteCompanyRoleTest extends TestCase
{
    use RefreshDatabase;

    public function test_company_admin_can_delete_an_unassigned_custom_role(): void
    {
        [$company, $admin] = $this->companyAdmin('delete-custom');
        $role = $this->createRole($company, 'temporary');

        $this->actingAs($admin);
        session(['current_company_id' => $company->id]);

        app(DeleteCompanyRole::class)->handle($role, $admin);

        $this->assertDatabaseMissing('roles', ['id' => $role->id]);
    }

    public function test_assigned_role_cannot_be_deleted(): void
    {
        [$company, $admin] = $this->companyAdmin('assigned-role');
        $role = $this->createRole($company, 'assigned');
        $member = User::factory()->create();
        $member->companies()->attach($company->id, [
            'role_id' => $role->id,
            'is_default' => true,
        ]);

        $this->actingAs($admin);
        session(['current_company_id' => $company->id]);

        $this->expectException(ValidationException::class);
        app(DeleteCompanyRole::class)->handle($role, $admin);
    }


    public function test_role_assigned_through_multi_role_membership_cannot_be_deleted(): void
    {
        [$company, $admin] = $this->companyAdmin('multi-role-assignment');
        $role = $this->createRole($company, 'secondary-role');
        $member = User::factory()->create();
        $member->companies()->attach($company->id, [
            'role_id' => null,
            'is_default' => true,
        ]);

        $membershipId = DB::table('company_user')
            ->where('company_id', $company->id)
            ->where('user_id', $member->id)
            ->value('id');

        DB::table('company_user_role')->insert([
            'company_user_id' => $membershipId,
            'role_id' => $role->id,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $this->actingAs($admin);
        session(['current_company_id' => $company->id]);

        $this->expectException(ValidationException::class);
        app(DeleteCompanyRole::class)->handle($role, $admin);
    }

    public function test_role_with_child_roles_cannot_be_deleted(): void
    {
        [$company, $admin] = $this->companyAdmin('parent-role');
        $parent = $this->createRole($company, 'parent');
        $this->createRole($company, 'child', false, $parent);

        $this->actingAs($admin);
        session(['current_company_id' => $company->id]);

        $this->expectException(ValidationException::class);
        app(DeleteCompanyRole::class)->handle($parent, $admin);
    }

    public function test_system_role_cannot_be_deleted_even_when_called_directly(): void
    {
        [$company, $admin] = $this->companyAdmin('system-role');
        $systemRole = $this->createRole($company, 'system', true);

        $this->actingAs($admin);
        session(['current_company_id' => $company->id]);

        $this->expectException(ValidationException::class);
        app(DeleteCompanyRole::class)->handle($systemRole, $admin);
    }

    public function test_non_admin_without_role_management_permission_cannot_delete_roles(): void
    {
        $company = $this->createCompany('unauthorized');
        $staffRole = $this->createRole($company, 'staff');
        $targetRole = $this->createRole($company, 'target');
        $user = User::factory()->create();
        $user->companies()->attach($company->id, [
            'role_id' => $staffRole->id,
            'is_default' => true,
        ]);

        $this->actingAs($user);
        session(['current_company_id' => $company->id]);

        $this->expectException(AuthorizationException::class);
        app(DeleteCompanyRole::class)->handle($targetRole, $user);
    }

    /**
     * @return array{Company, User}
     */
    private function companyAdmin(string $suffix): array
    {
        $company = $this->createCompany($suffix);
        $adminRole = $this->createRole($company, 'admin', true);
        $permission = Permission::query()->create([
            'name' => 'roles.manage',
            'label' => 'مدیریت نقش‌ها',
            'group' => 'system',
        ]);
        $adminRole->permissions()->attach($permission);

        $admin = User::factory()->create();
        $admin->companies()->attach($company->id, [
            'role_id' => $adminRole->id,
            'is_default' => true,
        ]);

        $membershipId = DB::table('company_user')
            ->where('company_id', $company->id)
            ->where('user_id', $admin->id)
            ->value('id');
        DB::table('company_user_role')->insert([
            'company_user_id' => $membershipId,
            'role_id' => $adminRole->id,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        return [$company, $admin];
    }

    private function createCompany(string $slug): Company
    {
        return Company::query()->create([
            'name' => 'شرکت '.$slug,
            'slug' => $slug,
            'is_active' => true,
        ]);
    }

    private function createRole(Company $company, string $name, bool $system = false, ?Role $parent = null): Role
    {
        return Role::query()->create([
            'company_id' => $company->id,
            'parent_role_id' => $parent?->id,
            'name' => $name,
            'label' => $name,
            'is_system' => $system,
            'inherit_permissions' => false,
        ]);
    }
}
