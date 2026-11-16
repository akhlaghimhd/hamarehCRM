<?php

namespace Tests\Feature;

use App\Actions\Identity\CreateCompanyForUser;
use App\Models\Company;
use App\Models\Permission;
use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
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

    public function test_new_company_does_not_replace_existing_default_membership(): void
    {
        $creator = User::factory()->create();
        $existingCompany = Company::query()->create([
            'name' => 'شرکت قبلی',
            'slug' => 'old-company',
            'is_active' => true,
        ]);
        $creator->companies()->attach($existingCompany->id, ['is_default' => true]);

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
        $creator->companies()->attach($existingCompany->id, ['is_default' => true]);
        $existingCompany->delete();

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

    public function test_company_creation_rolls_back_when_a_duplicate_slug_is_rejected(): void
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
