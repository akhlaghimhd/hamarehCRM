<?php

namespace Tests\Feature;

use App\Actions\Identity\UpdateUserAccount;
use App\Models\Company;
use App\Models\Permission;
use App\Models\Role;
use App\Models\User;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

class UpdateUserAccountTest extends TestCase
{
    use RefreshDatabase;

    public function test_company_admin_cannot_globally_deactivate_a_user_with_other_company_memberships(): void
    {
        [$company, $actor] = $this->companyWithAdmin('account-status-a');
        $otherCompany = $this->createCompany('account-status-b');
        $targetRole = $this->createRole($company, 'staff');
        $target = User::factory()->create();
        $target->companies()->attach($company->id, ['role_id' => $targetRole->id]);
        $target->companies()->attach($otherCompany->id, ['is_default' => true]);

        $this->actingAs($actor);
        session(['current_company_id' => $company->id]);

        $this->expectException(ValidationException::class);

        app(UpdateUserAccount::class)->assertCanChangeStatus(
            $target,
            ['is_active' => false],
            $actor,
            $company->id,
        );
    }


    public function test_company_admin_cannot_reactivate_a_multi_company_account_globally(): void
    {
        [$company, $actor] = $this->companyWithAdmin('account-reactivation-a');
        $otherCompany = $this->createCompany('account-reactivation-b');
        $target = User::factory()->create(['is_active' => false]);
        $target->companies()->attach($company->id);
        $target->companies()->attach($otherCompany->id);

        $this->actingAs($actor);
        session(['current_company_id' => $company->id]);

        $this->expectException(ValidationException::class);

        app(UpdateUserAccount::class)->assertCanChangeStatus(
            $target,
            ['is_active' => true],
            $actor,
            $company->id,
        );
    }


    public function test_company_admin_cannot_deactivate_the_last_active_company_admin(): void
    {
        $company = $this->createCompany('last-admin-protection');
        $managerRole = $this->createRole($company, 'identity-manager');
        $permission = Permission::query()->create([
            'name' => 'users.manage',
            'label' => 'مدیریت کاربران',
            'group' => 'identity',
        ]);
        $managerRole->permissions()->attach($permission);

        $actor = User::factory()->create();
        $actor->companies()->attach($company->id, [
            'role_id' => $managerRole->id,
            'is_default' => true,
        ]);

        $adminRole = $this->createRole($company, 'admin');
        $target = User::factory()->create();
        $target->companies()->attach($company->id, [
            'role_id' => $adminRole->id,
            'is_default' => true,
        ]);

        $this->actingAs($actor);
        session(['current_company_id' => $company->id]);

        $this->expectException(ValidationException::class);

        app(UpdateUserAccount::class)->assertCanChangeStatus(
            $target,
            ['is_active' => false],
            $actor,
            $company->id,
        );
    }


    public function test_company_admin_can_deactivate_an_admin_when_another_active_admin_remains(): void
    {
        $company = $this->createCompany('two-admins');
        $actorRole = $this->createRole($company, 'identity-manager');
        $permission = Permission::query()->create([
            'name' => 'users.manage',
            'label' => 'مدیریت کاربران',
            'group' => 'identity',
        ]);
        $actorRole->permissions()->attach($permission);

        $actor = User::factory()->create();
        $actor->companies()->attach($company->id, [
            'role_id' => $actorRole->id,
            'is_default' => true,
        ]);

        $adminRole = $this->createRole($company, 'admin');
        $target = User::factory()->create();
        $target->companies()->attach($company->id, [
            'role_id' => $adminRole->id,
            'is_default' => true,
        ]);
        $remainingAdmin = User::factory()->create();
        $remainingAdmin->companies()->attach($company->id, [
            'role_id' => $adminRole->id,
            'is_default' => true,
        ]);

        $this->actingAs($actor);
        session(['current_company_id' => $company->id]);

        app(UpdateUserAccount::class)->assertCanChangeStatus(
            $target,
            ['is_active' => false],
            $actor,
            $company->id,
        );

        $this->assertTrue($remainingAdmin->fresh()->is_active);
    }

    public function test_company_admin_can_deactivate_an_account_that_belongs_only_to_current_company(): void
    {
        [$company, $actor] = $this->companyWithAdmin('account-status-single');
        $targetRole = $this->createRole($company, 'staff');
        $target = User::factory()->create();
        $target->companies()->attach($company->id, ['role_id' => $targetRole->id]);

        $this->actingAs($actor);
        session(['current_company_id' => $company->id]);

        app(UpdateUserAccount::class)->assertCanChangeStatus(
            $target,
            ['is_active' => false],
            $actor,
            $company->id,
        );

        $this->assertTrue($target->fresh()->is_active);
    }



    public function test_company_admin_can_reactivate_a_manager_who_has_direct_reports(): void
    {
        [$company, $actor] = $this->companyWithAdmin('account-status-reactivate-manager');
        $target = User::factory()->create(['is_active' => false]);
        $report = User::factory()->create();
        $target->companies()->attach($company->id);
        $report->companies()->attach($company->id, [
            'manager_user_id' => $target->id,
        ]);

        $this->actingAs($actor);
        session(['current_company_id' => $company->id]);

        app(UpdateUserAccount::class)->assertCanChangeStatus(
            $target,
            ['is_active' => true],
            $actor,
            $company->id,
        );

        $this->assertFalse($target->fresh()->is_active);
        $this->assertDatabaseHas('company_user', [
            'company_id' => $company->id,
            'user_id' => $report->id,
            'manager_user_id' => $target->id,
        ]);
    }

    public function test_company_admin_cannot_deactivate_a_user_who_has_direct_reports(): void
    {
        [$company, $actor] = $this->companyWithAdmin('account-status-manager');
        $targetRole = $this->createRole($company, 'staff');
        $target = User::factory()->create();
        $report = User::factory()->create();
        $target->companies()->attach($company->id, ['role_id' => $targetRole->id]);
        $report->companies()->attach($company->id, [
            'role_id' => $targetRole->id,
            'manager_user_id' => $target->id,
        ]);

        $this->actingAs($actor);
        session(['current_company_id' => $company->id]);

        $this->expectException(ValidationException::class);

        app(UpdateUserAccount::class)->assertCanChangeStatus(
            $target,
            ['is_active' => false],
            $actor,
            $company->id,
        );
    }

    public function test_company_admin_cannot_deactivate_their_own_account_from_company_management(): void
    {
        [$company, $actor] = $this->companyWithAdmin('account-status-self');

        $this->actingAs($actor);
        session(['current_company_id' => $company->id]);

        $this->expectException(ValidationException::class);

        app(UpdateUserAccount::class)->assertCanChangeStatus(
            $actor,
            ['is_active' => false],
            $actor,
            $company->id,
        );
    }

    public function test_status_change_requires_the_actor_current_company_context(): void
    {
        [$company, $actor] = $this->companyWithAdmin('account-status-context');
        $otherCompany = $this->createCompany('account-status-outside');
        $target = User::factory()->create();
        $target->companies()->attach($company->id);

        $this->actingAs($actor);
        session(['current_company_id' => $otherCompany->id]);

        $this->expectException(AuthorizationException::class);

        app(UpdateUserAccount::class)->assertCanChangeStatus(
            $target,
            ['is_active' => false],
            $actor,
            $company->id,
        );
    }

    public function test_account_status_change_is_audited_in_the_current_company(): void
    {
        [$company, $actor] = $this->companyWithAdmin('account-status-audit');
        $targetRole = $this->createRole($company, 'staff');
        $target = User::factory()->create();
        $target->companies()->attach($company->id, ['role_id' => $targetRole->id]);

        $this->actingAs($actor);
        session(['current_company_id' => $company->id]);

        app(UpdateUserAccount::class)->assertCanChangeStatus($target, ['is_active' => false], $actor, $company->id);
        $target->update(['is_active' => false]);
        app(UpdateUserAccount::class)->recordStatusChange($target, true, $actor, $company->id);

        $this->assertDatabaseHas('audit_events', [
            'company_id' => $company->id,
            'actor_user_id' => $actor->id,
            'auditable_id' => $target->id,
            'event' => 'account_status_changed',
        ]);
    }

    private function companyWithAdmin(string $suffix): array
    {
        $company = $this->createCompany($suffix);
        $adminRole = $this->createRole($company, 'admin');
        $actor = User::factory()->create();
        $actor->companies()->attach($company->id, [
            'role_id' => $adminRole->id,
            'is_default' => true,
        ]);

        return [$company, $actor];
    }

    private function createCompany(string $slug): Company
    {
        return Company::query()->create([
            'name' => $slug,
            'slug' => $slug,
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
        ]);
    }
}
