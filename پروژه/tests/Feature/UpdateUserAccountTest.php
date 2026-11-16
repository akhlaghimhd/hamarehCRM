<?php

namespace Tests\Feature;

use App\Actions\Identity\UpdateUserAccount;
use App\Models\Company;
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
