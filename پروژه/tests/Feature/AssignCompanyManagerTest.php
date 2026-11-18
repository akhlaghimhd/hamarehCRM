<?php

namespace Tests\Feature;

use App\Actions\Identity\AssignCompanyManager;
use App\Models\Company;
use App\Models\PlatformOwner;
use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class AssignCompanyManagerTest extends TestCase
{
    use RefreshDatabase;

    private function owner(): User
    {
        $owner = User::factory()->create();
        PlatformOwner::query()->create(['user_id' => $owner->id]);

        return $owner;
    }

    private function company(string $slug = 'manager-target'): Company
    {
        return Company::query()->create([
            'name' => 'شرکت مقصد',
            'slug' => $slug,
            'is_active' => true,
        ]);
    }

    public function test_platform_owner_can_assign_an_existing_account_as_company_admin(): void
    {
        $owner = $this->owner();
        $company = $this->company();
        $user = User::factory()->create();

        $assigned = app(AssignCompanyManager::class)->handle($company, [
            'mode' => 'existing',
            'user_id' => $user->id,
        ], $owner);

        $this->assertSame($user->id, $assigned->id);
        $this->assertDatabaseHas('company_user', [
            'company_id' => $company->id,
            'user_id' => $user->id,
        ]);
        $this->assertDatabaseHas('roles', [
            'company_id' => $company->id,
            'name' => 'admin',
        ]);
        $this->assertDatabaseHas('audit_events', [
            'company_id' => $company->id,
            'auditable_id' => $user->id,
            'event' => 'company_manager_assigned',
        ]);
        $this->assertDatabaseMissing('company_user', [
            'company_id' => $company->id,
            'user_id' => $owner->id,
        ]);
    }

    public function test_platform_owner_can_create_a_new_manager_account_without_changing_other_default_membership(): void
    {
        $owner = $this->owner();
        $company = $this->company('manager-new-account');
        $otherCompany = $this->company('manager-other-company');
        $user = User::factory()->create();
        $otherRole = Role::query()->create([
            'company_id' => $otherCompany->id,
            'name' => 'admin',
            'label' => 'مدیر شرکت',
            'is_system' => true,
        ]);
        $user->companies()->attach($otherCompany->id, [
            'role_id' => $otherRole->id,
            'is_default' => true,
        ]);

        $created = app(AssignCompanyManager::class)->handle($company, [
            'mode' => 'new',
            'name' => 'مدیر تازه',
            'username' => 'new-company-manager',
            'email' => 'new-manager@example.test',
            'password' => 'SecurePass123',
            'password_confirmation' => 'SecurePass123',
        ], $owner);

        $this->assertSame('new-company-manager', $created->username);
        $this->assertDatabaseHas('company_user', [
            'company_id' => $otherCompany->id,
            'user_id' => $user->id,
            'is_default' => true,
        ]);
        $this->assertDatabaseHas('company_user', [
            'company_id' => $company->id,
            'user_id' => $created->id,
            'is_default' => false,
        ]);
        $this->assertTrue(Hash::check('SecurePass123', $created->password));
    }

    public function test_company_manager_assignment_is_denied_to_unrelated_user(): void
    {
        $company = $this->company('manager-forbidden');
        $actor = User::factory()->create();

        $this->expectException(\Illuminate\Auth\Access\AuthorizationException::class);

        app(AssignCompanyManager::class)->handle($company, [
            'mode' => 'new',
            'name' => 'مدیر',
            'username' => 'forbidden-manager',
            'email' => 'forbidden@example.test',
            'password' => 'SecurePass123',
            'password_confirmation' => 'SecurePass123',
        ], $actor);
    }
}
