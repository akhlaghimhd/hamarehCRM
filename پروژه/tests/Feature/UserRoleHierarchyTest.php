<?php

namespace Tests\Feature;

use App\Actions\Identity\UpdateCompanyMembership;
use App\Actions\Identity\UpdateRoleHierarchy;
use App\Models\Company;
use App\Models\Permission;
use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

class UserRoleHierarchyTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_permissions_are_the_union_of_all_current_company_roles_and_inherited_permissions(): void
    {
        $company = $this->createCompany('one');
        $parent = $this->createRole($company, 'parent');
        $sales = $this->createRole($company, 'sales', $parent);
        $warehouse = $this->createRole($company, 'warehouse');

        $customerPermission = $this->createPermission('customers.view');
        $stockPermission = $this->createPermission('inventory.view');

        $parent->permissions()->attach($customerPermission);
        $warehouse->permissions()->attach($stockPermission);

        $user = User::factory()->create();
        $user->companies()->attach($company->id, [
            'role_id' => $sales->id,
            'is_default' => true,
        ]);

        $membershipId = DB::table('company_user')
            ->where('company_id', $company->id)
            ->where('user_id', $user->id)
            ->value('id');

        DB::table('company_user_role')->insert([
            ['company_user_id' => $membershipId, 'role_id' => $sales->id, 'created_at' => now(), 'updated_at' => now()],
            ['company_user_id' => $membershipId, 'role_id' => $warehouse->id, 'created_at' => now(), 'updated_at' => now()],
        ]);

        $this->actingAs($user);
        session(['current_company_id' => $company->id]);

        $this->assertCount(2, $user->currentRoles());
        $this->assertTrue($user->hasPermission('customers.view'));
        $this->assertTrue($user->hasPermission('inventory.view'));
        $this->assertFalse($user->hasPermission('orders.delete'));
    }

    public function test_permission_copy_is_a_snapshot_independent_from_later_source_changes(): void
    {
        $company = $this->createCompany('one');
        $source = $this->createRole($company, 'source');
        $child = $this->createRole($company, 'child');
        $firstPermission = $this->createPermission('customers.view');
        $laterPermission = $this->createPermission('orders.view');
        $source->permissions()->attach($firstPermission);

        app(UpdateRoleHierarchy::class)->handle($child, null, false, $source->id);

        $this->assertTrue($child->fresh()->permissions()->whereKey($firstPermission->id)->exists());

        $source->permissions()->attach($laterPermission);

        $this->assertFalse($child->fresh()->permissions()->whereKey($laterPermission->id)->exists());
        $this->assertEqualsCanonicalizing(
            [$firstPermission->id],
            $child->fresh()->effectivePermissionIds()->all(),
        );
    }

    public function test_role_parent_cannot_create_a_cycle(): void
    {
        $company = $this->createCompany('one');
        $parent = $this->createRole($company, 'parent');
        $child = $this->createRole($company, 'child', $parent);

        $this->expectException(ValidationException::class);
        app(UpdateRoleHierarchy::class)->handle($parent, $child->id, true);
    }

    public function test_role_parent_cannot_cross_company_boundary(): void
    {
        $company = $this->createCompany('one');
        $otherCompany = $this->createCompany('two');
        $child = $this->createRole($company, 'child');
        $foreignRole = $this->createRole($otherCompany, 'foreign');

        $this->expectException(ValidationException::class);
        app(UpdateRoleHierarchy::class)->handle($child, $foreignRole->id, true);
    }

    public function test_membership_manager_tree_is_company_scoped_and_keeps_manager_change_history(): void
    {
        $company = $this->createCompany('one');
        $otherCompany = $this->createCompany('two');
        $role = $this->createRole($company, 'staff');
        $role->permissions()->attach($this->createPermission('users.manage'));

        $manager = User::factory()->create();
        $employee = User::factory()->create();
        $grandchild = User::factory()->create();
        $foreignUser = User::factory()->create();

        $manager->companies()->attach($company->id, ['role_id' => $role->id, 'is_default' => true]);
        $employee->companies()->attach($company->id, ['role_id' => $role->id, 'is_default' => true]);
        $grandchild->companies()->attach($company->id, ['role_id' => $role->id, 'is_default' => true]);
        $foreignUser->companies()->attach($otherCompany->id, ['is_default' => true]);

        $this->actingAs($manager);
        session(['current_company_id' => $company->id]);

        $action = app(UpdateCompanyMembership::class);
        $action->handle($company->id, $employee->id, [$role->id], $manager->id, $manager->id);
        $action->handle($company->id, $grandchild->id, [$role->id], $employee->id, $manager->id);

        $this->assertEqualsCanonicalizing(
            [$employee->id, $grandchild->id],
            $manager->managedUserIds()->all(),
        );
        $this->assertNotContains($foreignUser->id, $manager->managedUserIds()->all());

        $action->handle($company->id, $employee->id, [$role->id], null, $manager->id);

        $this->assertSame(2, DB::table('manager_membership_histories')
            ->where('company_id', $company->id)
            ->where('user_id', $employee->id)
            ->count());
        $this->assertSame([], $manager->fresh()->managedUserIds()->all());
    }

    public function test_membership_manager_cannot_be_self_or_create_a_reporting_cycle(): void
    {
        $company = $this->createCompany('one');
        $role = $this->createRole($company, 'staff');
        $first = User::factory()->create();
        $second = User::factory()->create();

        $first->companies()->attach($company->id, ['role_id' => $role->id, 'is_default' => true]);
        $second->companies()->attach($company->id, ['role_id' => $role->id, 'is_default' => true]);

        $action = app(UpdateCompanyMembership::class);
        $action->handle($company->id, $second->id, [$role->id], $first->id, $first->id);

        $this->expectException(ValidationException::class);
        $action->handle($company->id, $first->id, [$role->id], $second->id, $first->id);
    }

    public function test_team_activity_permissions_separately_control_view_and_management(): void
    {
        $company = $this->createCompany('team');
        $otherCompany = $this->createCompany('outside');
        $managerRole = $this->createRole($company, 'manager');
        $staffRole = $this->createRole($company, 'staff');

        $viewPermission = $this->createPermission('activities.view');
        $teamViewPermission = $this->createPermission('team.activities.view');
        $teamManagePermission = $this->createPermission('team.activities.manage');

        $usersManagePermission = $this->createPermission('users.manage');
        $managerRole->permissions()->attach([$viewPermission->id, $teamViewPermission->id, $teamManagePermission->id, $usersManagePermission->id]);

        $manager = User::factory()->create();
        $staff = User::factory()->create();
        $foreign = User::factory()->create();

        $manager->companies()->attach($company->id, ['role_id' => $managerRole->id, 'is_default' => true]);
        $staff->companies()->attach($company->id, ['role_id' => $staffRole->id, 'is_default' => true]);
        $foreign->companies()->attach($otherCompany->id, ['is_default' => true]);

        app(UpdateCompanyMembership::class)->handle(
            $company->id,
            $staff->id,
            [$staffRole->id],
            $manager->id,
            $manager->id,
        );

        $this->actingAs($manager);
        session(['current_company_id' => $company->id]);

        $this->assertTrue($manager->canViewActivityOwner($staff->id));
        $this->assertTrue($manager->canManageActivityOwner($staff->id));
        $this->assertFalse($manager->canViewActivityOwner($foreign->id));

        $managerRole->permissions()->detach($teamManagePermission->id);

        $this->assertTrue($manager->canViewActivityOwner($staff->id));
        $this->assertFalse($manager->canManageActivityOwner($staff->id));
    }

    private function createCompany(string $suffix): Company
    {
        return Company::query()->create([
            'name' => 'Company '.$suffix,
            'slug' => 'company-'.$suffix,
            'is_active' => true,
        ]);
    }

    private function createRole(Company $company, string $name, ?Role $parent = null): Role
    {
        return Role::query()->create([
            'company_id' => $company->id,
            'parent_role_id' => $parent?->id,
            'inherit_permissions' => true,
            'name' => $name,
            'label' => $name,
            'is_system' => false,
        ]);
    }

    private function createPermission(string $name): Permission
    {
        return Permission::query()->create([
            'name' => $name,
            'label' => $name,
            'group' => 'test',
        ]);
    }
}
