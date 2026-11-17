<?php

namespace Tests\Feature;

use App\Actions\Identity\CreateCompanyForUser;
use App\Actions\Identity\SetCompanyStatus;
use App\Filament\Resources\CompanyResource;
use App\Models\Company;
use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

class CompanyResourceTest extends TestCase
{
    use RefreshDatabase;

    public function test_company_list_includes_every_company_the_user_belongs_to(): void
    {
        [$first, $admin] = $this->companyAdmin('first');
        [$second] = $this->companyAdmin('second', $admin);
        $this->companyAdmin('not-a-member');

        $this->actingAs($admin);
        session(['current_company_id' => $first->id]);

        $visibleIds = CompanyResource::getEloquentQuery()->pluck('companies.id')->all();

        $this->assertEqualsCanonicalizing([$first->id, $second->id], $visibleIds);
    }

    public function test_company_status_can_only_be_changed_by_its_own_admin_and_is_audited(): void
    {
        [$company, $admin] = $this->companyAdmin('status');
        $this->actingAs($admin);
        session(['current_company_id' => $company->id]);

        $this->assertFalse(app(SetCompanyStatus::class)->handle($company, false, $admin));
        $this->assertDatabaseHas('companies', [
            'id' => $company->id,
            'is_active' => false,
        ]);
        $this->assertDatabaseHas('audit_events', [
            'company_id' => $company->id,
            'auditable_id' => $company->id,
            'event' => 'deactivated',
        ]);

        $this->assertTrue(app(SetCompanyStatus::class)->handle($company->fresh(), true, $admin));
        $this->assertDatabaseHas('companies', [
            'id' => $company->id,
            'is_active' => true,
        ]);
    }

    public function test_persian_national_id_and_phone_digits_are_normalized_before_storage(): void
    {
        $creator = User::factory()->create();

        $company = app(CreateCompanyForUser::class)->handle([
            'name' => 'شرکت ارقام فارسی',
            'slug' => 'persian-digits-company',
            'national_id' => '۱۲۳۴۵۶۷۸۹۰۱',
            'phone' => '۰۲۱۱۲۳۴۵۶۷۸',
        ], $creator);

        $this->assertSame('12345678901', $company->national_id);
        $this->assertSame('02112345678', $company->phone);
        $this->assertTrue($company->is_active);
    }

    public function test_invalid_national_id_and_phone_lengths_are_rejected(): void
    {
        $creator = User::factory()->create();

        try {
            app(CreateCompanyForUser::class)->handle([
                'name' => 'شرکت نامعتبر',
                'slug' => 'invalid-company-numbers',
                'national_id' => '۱۲۳۴۵',
                'phone' => '۰۲۱۱۲۳',
            ], $creator);

            $this->fail('Expected invalid company contact numbers to be rejected.');
        } catch (ValidationException $exception) {
            $this->assertArrayHasKey('national_id', $exception->errors());
            $this->assertArrayHasKey('phone', $exception->errors());
            $this->assertDatabaseMissing('companies', ['slug' => 'invalid-company-numbers']);
        }
    }

    private function companyAdmin(string $suffix, ?User $user = null): array
    {
        $company = Company::query()->create([
            'name' => 'شرکت '.$suffix,
            'slug' => 'company-'.$suffix,
            'is_active' => true,
        ]);

        $adminRole = Role::query()->create([
            'company_id' => $company->id,
            'name' => 'admin',
            'label' => 'مدیر شرکت',
            'is_system' => true,
            'inherit_permissions' => false,
        ]);

        $user ??= User::factory()->create();
        $user->companies()->attach($company->id, [
            'role_id' => $adminRole->id,
            'is_default' => ! DB::table('company_user')->where('user_id', $user->id)->where('is_default', true)->exists(),
        ]);

        DB::table('company_user_role')->insert([
            'company_user_id' => DB::table('company_user')
                ->where('company_id', $company->id)
                ->where('user_id', $user->id)
                ->value('id'),
            'role_id' => $adminRole->id,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        return [$company, $user];
    }
}
