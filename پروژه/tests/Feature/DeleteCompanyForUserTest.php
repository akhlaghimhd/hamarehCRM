<?php

namespace Tests\Feature;

use App\Actions\Identity\DeleteCompanyForUser;
use App\Filament\Pages\SwitchCompany;
use App\Models\Company;
use App\Models\Permission;
use App\Models\Role;
use App\Models\User;
use App\Support\CompanyContext;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DeleteCompanyForUserTest extends TestCase
{
    use RefreshDatabase;

    public function test_company_admin_can_soft_delete_company_and_recover_it_from_switcher(): void
    {
        $company = Company::query()->create([
            'name' => 'شرکت قابل بازیابی',
            'slug' => 'recoverable-company',
            'is_active' => true,
        ]);
        $role = Role::query()->create([
            'company_id' => $company->id,
            'name' => 'admin',
            'label' => 'مدیر شرکت',
        ]);
        $user = User::factory()->create();
        $user->companies()->attach($company->id, [
            'role_id' => $role->id,
            'is_default' => true,
        ]);

        $this->actingAs($user);
        session(['current_company_id' => $company->id]);

        app(DeleteCompanyForUser::class)->handle($company, $user);

        $this->assertSoftDeleted('companies', ['id' => $company->id]);
        $this->assertNull(app(CompanyContext::class)->currentCompanyId($user));
        $this->assertTrue(SwitchCompany::canAccess());
        $this->assertDatabaseHas('audit_events', [
            'company_id' => $company->id,
            'actor_user_id' => $user->id,
            'auditable_id' => $company->id,
            'event' => 'deleted',
        ]);
    }


    public function test_company_manager_with_permission_cannot_soft_delete_company(): void
    {
        $company = Company::query()->create([
            'name' => 'شرکت مدیر اجرایی',
            'slug' => 'company-manager-delete',
            'is_active' => true,
        ]);
        $role = Role::query()->create([
            'company_id' => $company->id,
            'name' => 'company-manager',
            'label' => 'مدیر اجرایی',
            'is_system' => false,
        ]);
        $permission = Permission::query()->create([
            'name' => 'companies.manage',
            'label' => 'مدیریت شرکت‌ها',
            'group' => 'system',
        ]);
        $role->permissions()->attach($permission);

        $user = User::factory()->create();
        $user->companies()->attach($company->id, [
            'role_id' => $role->id,
            'is_default' => true,
        ]);

        $this->actingAs($user);
        session(['current_company_id' => $company->id]);

        $this->expectException(AuthorizationException::class);

        app(DeleteCompanyForUser::class)->handle($company, $user);
    }

    public function test_non_admin_cannot_delete_company(): void
    {
        $company = Company::query()->create([
            'name' => 'شرکت غیرمجاز',
            'slug' => 'company-no-delete',
            'is_active' => true,
        ]);
        $role = Role::query()->create([
            'company_id' => $company->id,
            'name' => 'staff',
            'label' => 'کارمند',
        ]);
        $user = User::factory()->create();
        $user->companies()->attach($company->id, [
            'role_id' => $role->id,
            'is_default' => true,
        ]);

        $this->actingAs($user);
        session(['current_company_id' => $company->id]);

        $this->expectException(AuthorizationException::class);

        app(DeleteCompanyForUser::class)->handle($company, $user);
    }
}
