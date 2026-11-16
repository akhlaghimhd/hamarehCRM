<?php

namespace Tests\Feature;

use App\Actions\Identity\UpdateCompanyProfile;
use App\Filament\Pages\SwitchCompany;
use App\Filament\Resources\CompanyResource\Pages\EditCompany;
use App\Models\Company;
use App\Models\Permission;
use App\Models\Role;
use App\Models\User;
use App\Support\CompanyContext;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Livewire\Livewire;
use Tests\TestCase;

class UpdateCompanyProfileTest extends TestCase
{
    use RefreshDatabase;

    public function test_company_admin_can_update_profile_and_audit_changes(): void
    {
        [$company, $user] = $this->companyWithAdmin();

        $this->actingAs($user);
        session(['current_company_id' => $company->id]);

        $updated = app(UpdateCompanyProfile::class)->handle($company, [
            'name' => 'شرکت هم‌ره نو',
            'slug' => 'hamareh-new',
            'national_id' => '1234567890',
            'registration_number' => 'REG-10',
            'phone' => '02112345678',
            'email' => 'office@example.test',
            'address' => 'تهران',
            'is_active' => true,
        ], $user);

        $this->assertSame('شرکت هم‌ره نو', $updated->name);
        $this->assertSame('hamareh-new', $updated->slug);
        $this->assertDatabaseHas('audit_events', [
            'company_id' => $company->id,
            'actor_user_id' => $user->id,
            'auditable_type' => $company->getMorphClass(),
            'auditable_id' => $company->id,
            'event' => 'updated',
        ]);

        $event = DB::table('audit_events')
            ->where('company_id', $company->id)
            ->where('auditable_type', $company->getMorphClass())
            ->where('auditable_id', $company->id)
            ->latest('id')
            ->first();

        $this->assertSame('شرکت قدیمی', json_decode($event->old_values, true)['name']);
        $this->assertSame('شرکت هم‌ره نو', json_decode($event->new_values, true)['name']);
    }


    public function test_deactivating_company_redirects_admin_to_company_recovery_page(): void
    {
        [$company, $user] = $this->companyWithAdmin();

        $this->actingAs($user);
        session(['current_company_id' => $company->id]);

        Livewire::test(EditCompany::class, ['record' => $company->getRouteKey()])
            ->fillForm([
                'name' => $company->name,
                'slug' => $company->slug,
                'national_id' => $company->national_id,
                'registration_number' => $company->registration_number,
                'phone' => $company->phone,
                'email' => $company->email,
                'address' => $company->address,
                'is_active' => false,
            ])
            ->call('save')
            ->assertHasNoFormErrors()
            ->assertRedirect(SwitchCompany::getUrl());
    }

    public function test_company_admin_can_deactivate_current_company_and_recover_it_later(): void
    {
        [$company, $user] = $this->companyWithAdmin();

        $this->actingAs($user);
        session(['current_company_id' => $company->id]);

        app(UpdateCompanyProfile::class)->handle($company, [
            'name' => $company->name,
            'slug' => $company->slug,
            'national_id' => $company->national_id,
            'registration_number' => $company->registration_number,
            'phone' => $company->phone,
            'email' => $company->email,
            'address' => $company->address,
            'is_active' => false,
        ], $user);

        $this->assertNull(app(CompanyContext::class)->currentCompanyId($user));
        $this->assertTrue(SwitchCompany::canAccess());
    }



    public function test_company_manager_with_permission_can_update_profile_without_changing_status(): void
    {
        $company = $this->createCompany('manager-profile-update');
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

        $updated = app(UpdateCompanyProfile::class)->handle($company, [
            'name' => 'نام به‌روزشده',
            'slug' => $company->slug,
            'national_id' => $company->national_id,
            'registration_number' => $company->registration_number,
            'phone' => $company->phone,
            'email' => $company->email,
            'address' => $company->address,
            'is_active' => true,
        ], $user);

        $this->assertSame('نام به‌روزشده', $updated->name);
        $this->assertTrue($updated->is_active);
    }

    public function test_company_manager_with_permission_cannot_deactivate_company(): void
    {
        $company = $this->createCompany('manager-cannot-deactivate');
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

        app(UpdateCompanyProfile::class)->handle($company, [
            'name' => $company->name,
            'slug' => $company->slug,
            'national_id' => $company->national_id,
            'registration_number' => $company->registration_number,
            'phone' => $company->phone,
            'email' => $company->email,
            'address' => $company->address,
            'is_active' => false,
        ], $user);
    }

    public function test_non_admin_cannot_update_company_profile(): void
    {
        $company = $this->createCompany('staff-company');
        $staffRole = Role::query()->create([
            'company_id' => $company->id,
            'name' => 'staff',
            'label' => 'کارمند',
        ]);
        $user = User::factory()->create();
        $user->companies()->attach($company->id, [
            'role_id' => $staffRole->id,
            'is_default' => true,
        ]);

        $this->actingAs($user);
        session(['current_company_id' => $company->id]);

        $this->expectException(AuthorizationException::class);

        app(UpdateCompanyProfile::class)->handle($company, [
            'name' => 'ویرایش غیرمجاز',
            'slug' => 'unauthorized',
            'is_active' => true,
        ], $user);
    }


    public function test_company_admin_cannot_set_a_non_latin_slug(): void
    {
        [$company, $user] = $this->companyWithAdmin();

        $this->actingAs($user);
        session(['current_company_id' => $company->id]);

        $this->expectException(ValidationException::class);

        app(UpdateCompanyProfile::class)->handle($company, [
            'name' => $company->name,
            'slug' => 'شرکت-جدید',
            'national_id' => $company->national_id,
            'registration_number' => $company->registration_number,
            'phone' => $company->phone,
            'email' => $company->email,
            'address' => $company->address,
            'is_active' => true,
        ], $user);
    }

    public function test_company_admin_cannot_assign_a_duplicate_slug(): void
    {
        [$company, $user] = $this->companyWithAdmin();
        $this->createCompany('already-used');

        $this->actingAs($user);
        session(['current_company_id' => $company->id]);

        $this->expectException(ValidationException::class);

        app(UpdateCompanyProfile::class)->handle($company, [
            'name' => $company->name,
            'slug' => 'already-used',
            'national_id' => $company->national_id,
            'registration_number' => $company->registration_number,
            'phone' => $company->phone,
            'email' => $company->email,
            'address' => $company->address,
            'is_active' => true,
        ], $user);
    }

    /**
     * @return array{Company, User}
     */
    private function companyWithAdmin(): array
    {
        $company = $this->createCompany('old-company');
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

        return [$company, $user];
    }

    private function createCompany(string $slug): Company
    {
        return Company::query()->create([
            'name' => 'شرکت قدیمی',
            'slug' => $slug,
            'is_active' => true,
        ]);
    }
}
