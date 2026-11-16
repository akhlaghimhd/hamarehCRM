<?php

namespace Tests\Feature;

use App\Actions\Identity\CreateCompanyForUser;
use App\Filament\Resources\CompanyResource\Pages\CreateCompany;
use App\Models\Company;
use App\Models\Permission;
use App\Models\Role;
use App\Models\User;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Livewire\Livewire;
use Tests\TestCase;

class CreateCompanyForUserTest extends TestCase
{
    use RefreshDatabase;

    public function test_company_creator_becomes_admin_member_and_company_is_selected(): void
    {
        $creator = User::factory()->create();
        $permission = Permission::query()->create([
            'name' => 'companies.manage',
            'label' => 'مدیریت شرکت‌ها',
            'group' => 'سیستم',
        ]);

        $company = app(CreateCompanyForUser::class)->handle([
            'name' => 'شرکت جدید',
            'slug' => 'new-company',
            'is_active' => true,
        ], $creator);

        $role = Role::query()->where('company_id', $company->id)->where('name', 'admin')->firstOrFail();
        $membershipId = DB::table('company_user')
            ->where('company_id', $company->id)
            ->where('user_id', $creator->id)
            ->value('id');

        $this->assertDatabaseHas('company_user', [
            'id' => $membershipId,
            'role_id' => $role->id,
            'is_default' => true,
        ]);
        $this->assertTrue($role->permissions()->whereKey($permission->id)->exists());
        $this->assertDatabaseHas('company_user_role', [
            'company_user_id' => $membershipId,
            'role_id' => $role->id,
        ]);
        $this->assertSame($company->id, (int) session('current_company_id'));
        $this->assertDatabaseHas('audit_events', [
            'company_id' => $company->id,
            'actor_user_id' => $creator->id,
            'auditable_id' => $company->id,
            'event' => 'created',
        ]);

        $this->actingAs($creator->fresh());
        $this->assertTrue(auth()->user()->isCompanyAdmin());
    }


    public function test_first_company_can_be_created_from_the_filament_resource_page(): void
    {
        $creator = User::factory()->create();

        $this->actingAs($creator);
        session()->forget('current_company_id');

        Livewire::test(CreateCompany::class)
            ->fillForm([
                'name' => 'شرکت آغازین',
                'slug' => 'first-company-ui',
                'is_active' => true,
            ])
            ->call('create')
            ->assertHasNoErrors();

        $company = Company::query()->where('slug', 'first-company-ui')->firstOrFail();

        $this->assertSame($company->id, (int) session('current_company_id'));
        $this->assertTrue($creator->fresh()->isCompanyAdmin());
    }

    public function test_new_company_does_not_replace_existing_default_membership(): void
    {
        $creator = User::factory()->create();
        $existingCompany = Company::query()->create([
            'name' => 'شرکت قبلی',
            'slug' => 'old-company',
            'is_active' => true,
        ]);
        $adminRole = Role::query()->create([
            'company_id' => $existingCompany->id,
            'name' => 'admin',
            'label' => 'مدیر شرکت',
            'is_system' => true,
        ]);
        $creator->companies()->attach($existingCompany->id, [
            'role_id' => $adminRole->id,
            'is_default' => true,
        ]);
        $this->actingAs($creator);
        session(['current_company_id' => $existingCompany->id]);

        $newCompany = app(CreateCompanyForUser::class)->handle([
            'name' => 'شرکت جدید',
            'slug' => 'new-company',
            'is_active' => true,
        ], $creator);

        $this->assertDatabaseHas('company_user', [
            'company_id' => $existingCompany->id,
            'user_id' => $creator->id,
            'is_default' => true,
        ]);
        $this->assertDatabaseHas('company_user', [
            'company_id' => $newCompany->id,
            'user_id' => $creator->id,
            'is_default' => false,
        ]);
        $this->assertSame($newCompany->id, (int) session('current_company_id'));
    }


    public function test_soft_deleted_default_company_does_not_create_a_second_default(): void
    {
        $creator = User::factory()->create();
        $existingCompany = Company::query()->create([
            'name' => 'شرکت پیش‌فرض حذف‌شده',
            'slug' => 'old-deleted-default-company',
            'is_active' => true,
        ]);
        $existingAdminRole = Role::query()->create([
            'company_id' => $existingCompany->id,
            'name' => 'admin',
            'label' => 'مدیر شرکت',
            'is_system' => true,
        ]);
        $creator->companies()->attach($existingCompany->id, [
            'role_id' => $existingAdminRole->id,
            'is_default' => true,
        ]);
        $existingCompany->delete();

        $activeCompany = Company::query()->create([
            'name' => 'شرکت فعال',
            'slug' => 'active-company',
            'is_active' => true,
        ]);
        $activeAdminRole = Role::query()->create([
            'company_id' => $activeCompany->id,
            'name' => 'admin',
            'label' => 'مدیر شرکت',
            'is_system' => true,
        ]);
        $creator->companies()->attach($activeCompany->id, [
            'role_id' => $activeAdminRole->id,
            'is_default' => false,
        ]);
        $this->actingAs($creator);
        session(['current_company_id' => $activeCompany->id]);

        $newCompany = app(CreateCompanyForUser::class)->handle([
            'name' => 'شرکت جدید',
            'slug' => 'new-after-deleted-default',
            'is_active' => true,
        ], $creator);

        $this->assertDatabaseHas('company_user', [
            'company_id' => $existingCompany->id,
            'user_id' => $creator->id,
            'is_default' => true,
        ]);
        $this->assertDatabaseHas('company_user', [
            'company_id' => $newCompany->id,
            'user_id' => $creator->id,
            'is_default' => false,
        ]);
    }




    public function test_non_admin_member_cannot_create_another_company(): void
    {
        $creator = User::factory()->create();
        $existingCompany = Company::query()->create([
            'name' => 'شرکت کارمند',
            'slug' => 'staff-company-create',
            'is_active' => true,
        ]);
        $staffRole = Role::query()->create([
            'company_id' => $existingCompany->id,
            'name' => 'staff',
            'label' => 'کارمند',
            'is_system' => false,
        ]);
        $creator->companies()->attach($existingCompany->id, [
            'role_id' => $staffRole->id,
            'is_default' => true,
        ]);

        $this->actingAs($creator);
        session(['current_company_id' => $existingCompany->id]);

        $this->expectException(AuthorizationException::class);

        app(CreateCompanyForUser::class)->handle([
            'name' => 'شرکت غیرمجاز',
            'slug' => 'unauthorized-company',
            'is_active' => true,
        ], $creator);
    }



    public function test_company_manager_with_company_permission_cannot_create_another_company(): void
    {
        $creator = User::factory()->create();
        $existingCompany = Company::query()->create([
            'name' => 'شرکت مدیر اجرایی',
            'slug' => 'company-manager-create',
            'is_active' => true,
        ]);
        $managerRole = Role::query()->create([
            'company_id' => $existingCompany->id,
            'name' => 'company-manager',
            'label' => 'مدیر اجرایی',
            'is_system' => false,
        ]);
        $permission = Permission::query()->create([
            'name' => 'companies.manage',
            'label' => 'مدیریت شرکت‌ها',
            'group' => 'شرکت',
        ]);
        $managerRole->permissions()->attach($permission);
        $creator->companies()->attach($existingCompany->id, [
            'role_id' => $managerRole->id,
            'is_default' => true,
        ]);

        $this->actingAs($creator);
        session(['current_company_id' => $existingCompany->id]);

        $this->expectException(AuthorizationException::class);

        app(CreateCompanyForUser::class)->handle([
            'name' => 'شرکت دوم',
            'slug' => 'company-manager-second',
            'is_active' => true,
        ], $creator);
    }

    public function test_inactive_user_cannot_create_a_company(): void
    {
        $creator = User::factory()->create(['is_active' => false]);

        $this->expectException(AuthorizationException::class);

        app(CreateCompanyForUser::class)->handle([
            'name' => 'شرکت کاربر غیرفعال',
            'slug' => 'inactive-creator-company',
            'is_active' => true,
        ], $creator);
    }

    public function test_company_creation_rejects_non_latin_slug_before_creating_records(): void
    {
        $creator = User::factory()->create();

        try {
            app(CreateCompanyForUser::class)->handle([
                'name' => 'شرکت تازه',
                'slug' => 'شرکت-تازه',
                'is_active' => true,
            ], $creator);

            $this->fail('Expected invalid company slug to be rejected.');
        } catch (ValidationException) {
            $this->assertSame(0, Company::query()->count());
            $this->assertDatabaseMissing('company_user', [
                'user_id' => $creator->id,
            ]);
        }
    }

    public function test_company_creation_rejects_duplicate_slug_without_creating_membership(): void
    {
        $creator = User::factory()->create();
        Company::query()->create([
            'name' => 'شرکت قبلی',
            'slug' => 'duplicate-company',
            'is_active' => true,
        ]);

        try {
            app(CreateCompanyForUser::class)->handle([
                'name' => 'شرکت تکراری',
                'slug' => 'duplicate-company',
                'is_active' => true,
            ], $creator);

            $this->fail('Expected duplicate company slug to be rejected.');
        } catch (ValidationException) {
            $this->assertSame(1, Company::query()->where('slug', 'duplicate-company')->count());
            $this->assertDatabaseMissing('company_user', [
                'user_id' => $creator->id,
            ]);
        }
    }
}
