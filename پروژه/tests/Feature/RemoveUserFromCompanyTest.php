<?php

namespace Tests\Feature;

use App\Actions\Identity\RemoveUserFromCompany;
use App\Models\Company;
use App\Models\Permission;
use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

class RemoveUserFromCompanyTest extends TestCase
{
    use RefreshDatabase;

    public function test_removal_deletes_only_the_selected_company_membership(): void
    {
        [$company, $actor, $managerRole] = $this->companyAdmin('remove');
        $otherCompany = $this->createCompany('keep');
        $otherRole = $this->createRole($otherCompany, 'other-staff');

        $user = User::factory()->create();
        $user->companies()->attach($company->id, ['role_id' => $managerRole->id]);
        $user->companies()->attach($otherCompany->id, [
            'role_id' => $otherRole->id,
            'is_default' => true,
        ]);

        $this->actingAs($actor);
        session(['current_company_id' => $company->id]);

        app(RemoveUserFromCompany::class)->handle($company->id, $user->id, $actor);

        $this->assertDatabaseMissing('company_user', [
            'company_id' => $company->id,
            'user_id' => $user->id,
        ]);
        $this->assertDatabaseHas('company_user', [
            'company_id' => $otherCompany->id,
            'user_id' => $user->id,
            'is_default' => true,
        ]);
        $this->assertDatabaseHas('users', ['id' => $user->id]);
        $this->assertDatabaseMissing('company_user_role', [
            'company_user_id' => DB::table('company_user')
                ->where('company_id', $company->id)
                ->where('user_id', $user->id)
                ->value('id'),
        ]);
    }

    public function test_last_active_company_admin_cannot_be_removed(): void
    {
        [$company, $actor] = $this->companyAdmin('last-admin');

        $this->actingAs($actor);
        session(['current_company_id' => $company->id]);

        $this->expectException(ValidationException::class);

        app(RemoveUserFromCompany::class)->handle($company->id, $actor->id, $actor);
    }

    public function test_company_admin_can_be_removed_when_another_active_admin_exists(): void
    {
        [$company, $actor, $adminRole] = $this->companyAdmin('two-admins');
        $secondAdmin = User::factory()->create();
        $secondAdmin->companies()->attach($company->id, [
            'role_id' => $adminRole->id,
            'is_default' => true,
        ]);
        $membershipId = DB::table('company_user')
            ->where('company_id', $company->id)
            ->where('user_id', $secondAdmin->id)
            ->value('id');
        DB::table('company_user_role')->insert([
            'company_user_id' => $membershipId,
            'role_id' => $adminRole->id,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $this->actingAs($actor);
        session(['current_company_id' => $company->id]);

        app(RemoveUserFromCompany::class)->handle($company->id, $actor->id, $actor);

        $this->assertDatabaseMissing('company_user', [
            'company_id' => $company->id,
            'user_id' => $actor->id,
        ]);
        $this->assertDatabaseHas('company_user', [
            'company_id' => $company->id,
            'user_id' => $secondAdmin->id,
        ]);
    }

    public function test_membership_with_direct_reports_cannot_be_removed(): void
    {
        [$company, $actor, $adminRole] = $this->companyAdmin('manager');
        $employee = User::factory()->create();
        $employee->companies()->attach($company->id, [
            'role_id' => $adminRole->id,
            'manager_user_id' => $actor->id,
        ]);

        $this->actingAs($actor);
        session(['current_company_id' => $company->id]);

        $this->expectException(ValidationException::class);

        app(RemoveUserFromCompany::class)->handle($company->id, $actor->id, $actor);
    }

    public function test_actor_cannot_remove_membership_outside_current_company_context(): void
    {
        [$company, $actor, $role] = $this->companyAdmin('scope');
        $otherCompany = $this->createCompany('outside');
        $user = User::factory()->create();
        $user->companies()->attach($company->id, ['role_id' => $role->id]);

        $this->actingAs($actor);
        session(['current_company_id' => $otherCompany->id]);

        $this->expectException(ValidationException::class);

        app(RemoveUserFromCompany::class)->handle($company->id, $user->id, $actor);
    }

    /**
     * @return array{Company, User, Role}
     */
    private function companyAdmin(string $suffix): array
    {
        $company = $this->createCompany($suffix);
        $role = $this->createRole($company, 'admin');
        $permission = Permission::query()->create([
            'name' => 'users.manage',
            'label' => 'مدیریت کاربران',
            'group' => 'identity',
        ]);
        $role->permissions()->attach($permission);

        $actor = User::factory()->create();
        $actor->companies()->attach($company->id, [
            'role_id' => $role->id,
            'is_default' => true,
        ]);
        $membershipId = DB::table('company_user')
            ->where('company_id', $company->id)
            ->where('user_id', $actor->id)
            ->value('id');
        DB::table('company_user_role')->insert([
            'company_user_id' => $membershipId,
            'role_id' => $role->id,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        return [$company, $actor, $role];
    }

    private function createCompany(string $suffix): Company
    {
        return Company::query()->create([
            'name' => 'Company '.$suffix,
            'slug' => 'company-'.$suffix,
            'is_active' => true,
        ]);
    }

    private function createRole(Company $company, string $name): Role
    {
        return Role::query()->create([
            'company_id' => $company->id,
            'name' => $name,
            'label' => $name,
            'is_system' => $name === 'admin',
            'inherit_permissions' => false,
        ]);
    }
}
