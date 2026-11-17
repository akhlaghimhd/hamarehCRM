<?php

namespace Tests\Feature;

use App\Actions\Identity\CreateCompanyForUser;
use App\Filament\Resources\CompanyResource;
use App\Models\Company;
use App\Models\PlatformOwner;
use App\Models\Role;
use App\Models\User;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Livewire\Livewire;
use Tests\TestCase;

class PlatformCompanyCreationTest extends TestCase
{
    use RefreshDatabase;

    private function owner(): User
    {
        $user = User::factory()->create();
        PlatformOwner::query()->create(['user_id' => $user->id]);

        return $user;
    }

    private function managerData(array $overrides = []): array
    {
        return array_merge([
            'admin_name' => 'مدیر اولیه',
            'admin_username' => 'initial-admin',
            'admin_email' => 'initial-admin@example.test',
            'admin_password' => 'SecurePass123',
            'admin_password_confirmation' => 'SecurePass123',
        ], $overrides);
    }

    public function test_platform_owner_creates_company_and_separate_initial_admin_atomically(): void
    {
        $owner = $this->owner();

        $company = app(CreateCompanyForUser::class)->handle(array_merge([
            'name' => 'شرکت آزمایشی',
            'slug' => 'atomic-company',
        ], $this->managerData()), $owner);

        $manager = User::query()->where('username', 'initial-admin')->firstOrFail();
        $role = Role::query()->where('company_id', $company->id)->where('name', 'admin')->firstOrFail();
        $membershipId = DB::table('company_user')
            ->where('company_id', $company->id)
            ->where('user_id', $manager->id)
            ->value('id');

        $this->assertDatabaseHas('platform_owners', ['user_id' => $owner->id]);
        $this->assertDatabaseMissing('company_user', ['user_id' => $owner->id, 'company_id' => $company->id]);
        $this->assertDatabaseHas('company_user', [
            'id' => $membershipId,
            'company_id' => $company->id,
            'user_id' => $manager->id,
            'role_id' => $role->id,
            'is_default' => true,
        ]);
        $this->assertDatabaseHas('company_user_role', [
            'company_user_id' => $membershipId,
            'role_id' => $role->id,
        ]);
        $this->assertTrue($manager->isCompanyAdmin());
        $this->assertTrue($owner->fresh()->isPlatformOwner());
        $this->assertNull(session('current_company_id'));
    }

    public function test_only_platform_owner_can_create_company_even_if_user_is_company_admin(): void
    {
        $manager = User::factory()->create();
        $company = Company::query()->create(['name' => 'قدیمی', 'slug' => 'old-company']);
        $role = Role::query()->create([
            'company_id' => $company->id,
            'name' => 'admin',
            'label' => 'مدیر شرکت',
            'is_system' => true,
        ]);
        $manager->companies()->attach($company->id, ['role_id' => $role->id, 'is_default' => true]);
        $this->actingAs($manager);
        session(['current_company_id' => $company->id]);

        $this->expectException(AuthorizationException::class);

        app(CreateCompanyForUser::class)->handle(array_merge([
            'name' => 'شرکت غیرمجاز',
            'slug' => 'forbidden-company',
        ], $this->managerData(['admin_username' => 'forbidden-manager'])), $manager);
    }

    public function test_platform_owner_can_view_all_companies_without_membership(): void
    {
        $owner = $this->owner();
        $first = Company::query()->create(['name' => 'اول', 'slug' => 'owner-first']);
        $second = Company::query()->create(['name' => 'دوم', 'slug' => 'owner-second']);

        $this->actingAs($owner);

        $this->assertTrue(CompanyResource::canViewAny());
        $this->assertTrue(CompanyResource::canView($first));
        $this->assertEqualsCanonicalizing(
            [$first->id, $second->id],
            CompanyResource::getEloquentQuery()->pluck('companies.id')->all(),
        );
    }

    public function test_invalid_initial_admin_rolls_back_company_creation(): void
    {
        $owner = $this->owner();

        try {
            app(CreateCompanyForUser::class)->handle(array_merge([
                'name' => 'شرکت برگشت',
                'slug' => 'rolled-back-company',
            ], $this->managerData(['admin_email' => 'not-an-email'])), $owner);

            $this->fail('Expected manager validation to fail.');
        } catch (ValidationException) {
            $this->assertDatabaseMissing('companies', ['slug' => 'rolled-back-company']);
            $this->assertDatabaseMissing('users', ['username' => 'initial-admin']);
        }
    }

    public function test_company_resource_create_action_is_hidden_from_non_owners(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user);

        $this->assertFalse(CompanyResource::canCreate());
    }
}
