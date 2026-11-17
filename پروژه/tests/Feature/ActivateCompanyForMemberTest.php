<?php

namespace Tests\Feature;

use App\Actions\Identity\ActivateCompanyForMember;
use App\Filament\Pages\SwitchCompany;
use App\Models\Company;
use App\Models\Role;
use App\Models\User;
use App\Support\CompanyContext;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Validation\ValidationException;
use Livewire\Livewire;
use Tests\TestCase;

class ActivateCompanyForMemberTest extends TestCase
{
    use RefreshDatabase;

    public function test_company_admin_can_reactivate_an_inactive_company_and_restore_context(): void
    {
        [$company, $user] = $this->companyWithAdmin();
        $company->update(['is_active' => false]);

        $this->actingAs($user);
        session(['current_company_id' => $company->id]);

        $this->assertNull(app(CompanyContext::class)->currentCompanyId($user));
        $this->assertTrue(app(ActivateCompanyForMember::class)->hasRecoverableCompany($user));
        $this->assertTrue(SwitchCompany::canAccess());

        Livewire::test(SwitchCompany::class)
            ->assertSee('فعال‌سازی و ورود')
            ->call('activateCompany', $company->id);

        $this->assertDatabaseHas('companies', [
            'id' => $company->id,
            'is_active' => true,
            'deleted_at' => null,
        ]);
        $this->assertSame($company->id, app(CompanyContext::class)->currentCompanyId($user));
        $this->assertDatabaseHas('audit_events', [
            'company_id' => $company->id,
            'actor_user_id' => $user->id,
            'event' => 'activated',
        ]);
    }

    public function test_company_admin_can_restore_a_soft_deleted_company(): void
    {
        [$company, $user] = $this->companyWithAdmin();
        $company->delete();

        $this->actingAs($user);
        session(['current_company_id' => $company->id]);

        $this->assertTrue(app(ActivateCompanyForMember::class)->hasRecoverableCompany($user));

        app(ActivateCompanyForMember::class)->handle($company->id, $user);

        $this->assertDatabaseHas('companies', [
            'id' => $company->id,
            'is_active' => true,
            'deleted_at' => null,
        ]);
        $this->assertSame($company->id, app(CompanyContext::class)->currentCompanyId($user));
        $this->assertDatabaseHas('audit_events', [
            'company_id' => $company->id,
            'actor_user_id' => $user->id,
            'event' => 'restored',
        ]);
    }

    public function test_non_admin_member_cannot_reactivate_a_company(): void
    {
        $company = $this->createCompany('staff-inactive');
        $staffRole = Role::query()->create([
            'company_id' => $company->id,
            'name' => 'staff',
            'label' => 'کارمند',
            'is_system' => false,
        ]);
        $user = User::factory()->create();
        $user->companies()->attach($company->id, [
            'role_id' => $staffRole->id,
            'is_default' => true,
        ]);
        $company->update(['is_active' => false]);

        $this->actingAs($user);
        session(['current_company_id' => $company->id]);

        $this->assertFalse(app(ActivateCompanyForMember::class)->hasRecoverableCompany($user));

        $this->expectException(AuthorizationException::class);

        app(ActivateCompanyForMember::class)->handle($company->id, $user);
    }

    public function test_non_member_cannot_reactivate_another_company(): void
    {
        $company = $this->createCompany('private-inactive');
        $company->update(['is_active' => false]);
        $user = User::factory()->create();

        $this->actingAs($user);

        $this->expectException(ValidationException::class);

        app(ActivateCompanyForMember::class)->handle($company->id, $user);
    }

    /**
     * @return array{Company, User}
     */
    private function companyWithAdmin(): array
    {
        $company = $this->createCompany('admin-recovery');
        $role = Role::query()->create([
            'company_id' => $company->id,
            'name' => 'admin',
            'label' => 'مدیر شرکت',
            'is_system' => true,
        ]);
        $user = User::factory()->create();
        $user->companies()->attach($company->id, [
            'role_id' => $role->id,
            'is_default' => true,
        ]);

        return [$company, $user];
    }

    private function createCompany(string $slug): Company
    {
        return Company::query()->create([
            'name' => str($slug)->replace('-', ' ')->title()->toString(),
            'slug' => $slug,
            'is_active' => true,
        ]);
    }
}
