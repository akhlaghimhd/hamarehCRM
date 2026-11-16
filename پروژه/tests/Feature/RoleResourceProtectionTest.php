<?php

namespace Tests\Feature;

use App\Filament\Resources\RoleResource\Pages\CreateRole;
use App\Filament\Resources\RoleResource\Pages\EditRole;
use App\Models\Company;
use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class RoleResourceProtectionTest extends TestCase
{
    use RefreshDatabase;

    public function test_custom_role_cannot_be_created_with_reserved_admin_name(): void
    {
        [$company, $admin] = $this->companyAdmin('reserved-create');

        $this->actingAs($admin);
        session(['current_company_id' => $company->id]);

        Livewire::test(CreateRole::class)
            ->fillForm([
                'name' => 'admin',
                'label' => 'مدیر جعلی',
            ])
            ->call('create')
            ->assertHasFormErrors(['name']);

        $this->assertSame(
            1,
            Role::query()->where('company_id', $company->id)->where('name', 'admin')->count(),
        );
    }

    public function test_custom_role_cannot_be_renamed_to_admin(): void
    {
        [$company, $admin] = $this->companyAdmin('reserved-edit');
        $role = Role::query()->create([
            'company_id' => $company->id,
            'name' => 'sales',
            'label' => 'فروش',
            'is_system' => false,
            'inherit_permissions' => false,
        ]);

        $this->actingAs($admin);
        session(['current_company_id' => $company->id]);

        Livewire::test(EditRole::class, ['record' => $role->getRouteKey()])
            ->fillForm([
                'name' => 'admin',
                'label' => 'مدیر جعلی',
                'parent_role_id' => null,
                'inherit_permissions' => false,
                'permissions' => [],
            ])
            ->call('save')
            ->assertHasFormErrors(['name']);

        $this->assertDatabaseHas('roles', [
            'id' => $role->id,
            'name' => 'sales',
            'is_system' => false,
        ]);
    }

    /**
     * @return array{Company, User}
     */
    private function companyAdmin(string $suffix): array
    {
        $company = Company::query()->create([
            'name' => 'شرکت '.$suffix,
            'slug' => 'company-'.$suffix,
            'is_active' => true,
        ]);
        $role = Role::query()->create([
            'company_id' => $company->id,
            'name' => 'admin',
            'label' => 'مدیر شرکت',
            'is_system' => true,
            'inherit_permissions' => false,
        ]);
        $user = User::factory()->create();
        $user->companies()->attach($company->id, [
            'role_id' => $role->id,
            'is_default' => true,
        ]);

        return [$company, $user];
    }
}
