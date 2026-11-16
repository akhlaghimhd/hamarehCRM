<?php

namespace Tests\Feature;

use App\Actions\Identity\AddExistingUserToCompany;
use App\Models\Company;
use App\Models\Permission;
use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

class AddExistingUserToCompanyTest extends TestCase
{
    use RefreshDatabase;

    public function test_existing_user_is_added_with_company_scoped_roles_without_changing_other_memberships(): void
    {
        [$company, $actor, $role] = $this->companyAdmin('main');
        $otherCompany = $this->createCompany('other');
        $otherRole = $this->createRole($otherCompany, 'other-staff');

        $existingUser = User::factory()->create();
        $existingUser->companies()->attach($otherCompany->id, [
            'role_id' => $otherRole->id,
            'is_default' => true,
        ]);

        $this->actingAs($actor);
        session(['current_company_id' => $company->id]);

        app(AddExistingUserToCompany::class)->handle(
            $company->id,
            $existingUser->id,
            [$role->id],
            null,
            $actor,
        );

        $membership = DB::table('company_user')
            ->where('company_id', $company->id)
            ->where('user_id', $existingUser->id)
            ->first();

        $this->assertNotNull($membership);
        $this->assertFalse((bool) $membership->is_default);
        $this->assertDatabaseHas('company_user_role', [
            'company_user_id' => $membership->id,
            'role_id' => $role->id,
        ]);
        $this->assertDatabaseHas('company_user', [
            'company_id' => $otherCompany->id,
            'user_id' => $existingUser->id,
            'is_default' => true,
        ]);
    }

    public function test_first_company_membership_becomes_the_users_default_company(): void
    {
        [$company, $actor, $role] = $this->companyAdmin('first-default');
        $user = User::factory()->create();

        $this->actingAs($actor);
        session(['current_company_id' => $company->id]);

        app(AddExistingUserToCompany::class)->handle(
            $company->id,
            $user->id,
            [$role->id],
            null,
            $actor,
        );

        $this->assertDatabaseHas('company_user', [
            'company_id' => $company->id,
            'user_id' => $user->id,
            'is_default' => true,
        ]);
    }

    public function test_duplicate_membership_is_rejected(): void
    {
        [$company, $actor, $role] = $this->companyAdmin('duplicate');
        $user = User::factory()->create();
        $user->companies()->attach($company->id, ['role_id' => $role->id]);

        $this->actingAs($actor);
        session(['current_company_id' => $company->id]);

        $this->expectException(ValidationException::class);

        app(AddExistingUserToCompany::class)->handle(
            $company->id,
            $user->id,
            [$role->id],
            null,
            $actor,
        );
    }

    public function test_role_from_another_company_is_rejected(): void
    {
        [$company, $actor] = $this->companyAdmin('scope');
        $otherCompany = $this->createCompany('foreign');
        $foreignRole = $this->createRole($otherCompany, 'foreign-role');
        $user = User::factory()->create();

        $this->actingAs($actor);
        session(['current_company_id' => $company->id]);

        $this->expectException(ValidationException::class);

        app(AddExistingUserToCompany::class)->handle(
            $company->id,
            $user->id,
            [$foreignRole->id],
            null,
            $actor,
        );
    }

    public function test_actor_cannot_add_users_to_a_company_outside_current_context(): void
    {
        [$company, $actor, $role] = $this->companyAdmin('actor');
        $otherCompany = $this->createCompany('outside');
        $user = User::factory()->create();

        $this->actingAs($actor);
        session(['current_company_id' => $otherCompany->id]);

        $this->expectException(ValidationException::class);

        app(AddExistingUserToCompany::class)->handle(
            $company->id,
            $user->id,
            [$role->id],
            null,
            $actor,
        );
    }

    /**
     * @return array{Company, User, Role}
     */
    private function companyAdmin(string $suffix): array
    {
        $company = $this->createCompany($suffix);
        $role = $this->createRole($company, 'identity-manager');
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
