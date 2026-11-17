<?php

namespace Tests\Feature;

use App\Actions\Identity\CreateCompanyForUser;
use App\Models\Company;
use App\Models\PlatformOwner;
use App\Models\Role;
use App\Models\User;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

class CreateCompanyForUserTest extends TestCase
{
    use RefreshDatabase;

    private function owner(): User
    {
        $owner = User::factory()->create();
        PlatformOwner::query()->create(['user_id' => $owner->id]);
        return $owner;
    }

    private function attributes(string $slug = 'new-company'): array
    {
        return [
            'name' => 'شرکت جدید', 'slug' => $slug,
            'admin_name' => 'مدیر اولیه', 'admin_username' => 'manager-'.$slug,
            'admin_email' => $slug.'@example.test', 'admin_password' => 'SecurePass123',
            'admin_password_confirmation' => 'SecurePass123',
        ];
    }

    public function test_platform_owner_creates_company_with_a_distinct_initial_admin(): void
    {
        $owner = $this->owner();
        $company = app(CreateCompanyForUser::class)->handle($this->attributes(), $owner);
        $manager = User::query()->where('username', 'manager-new-company')->firstOrFail();

        $this->assertDatabaseHas('company_user', ['company_id' => $company->id, 'user_id' => $manager->id, 'is_default' => true]);
        $this->assertDatabaseMissing('company_user', ['company_id' => $company->id, 'user_id' => $owner->id]);
        $this->assertTrue($manager->isCompanyAdmin());
        $this->assertTrue($owner->fresh()->isPlatformOwner());
    }

    public function test_company_admin_cannot_create_another_company(): void
    {
        $user = User::factory()->create();
        $company = Company::query()->create(['name' => 'شرکت قبلی', 'slug' => 'previous-company']);
        $role = Role::query()->create(['company_id' => $company->id, 'name' => 'admin', 'label' => 'مدیر شرکت', 'is_system' => true]);
        $user->companies()->attach($company->id, ['role_id' => $role->id, 'is_default' => true]);

        $this->expectException(AuthorizationException::class);
        app(CreateCompanyForUser::class)->handle($this->attributes('second-company'), $user);
    }

    public function test_duplicate_slug_does_not_create_initial_admin(): void
    {
        $owner = $this->owner();
        Company::query()->create(['name' => 'قدیمی', 'slug' => 'duplicate-company']);

        try {
            app(CreateCompanyForUser::class)->handle($this->attributes('duplicate-company'), $owner);
            $this->fail('Expected duplicate slug validation to fail.');
        } catch (ValidationException) {
            $this->assertDatabaseMissing('users', ['username' => 'manager-duplicate-company']);
            $this->assertSame(1, Company::query()->where('slug', 'duplicate-company')->count());
        }
    }

    public function test_invalid_slug_is_rejected_without_creating_company_or_admin(): void
    {
        $owner = $this->owner();
        try {
            app(CreateCompanyForUser::class)->handle($this->attributes('شناسه-فارسی'), $owner);
            $this->fail('Expected slug validation to fail.');
        } catch (ValidationException) {
            $this->assertDatabaseMissing('companies', ['slug' => 'شناسه-فارسی']);
            $this->assertDatabaseMissing('users', ['username' => 'manager-شناسه-فارسی']);
        }
    }
}
