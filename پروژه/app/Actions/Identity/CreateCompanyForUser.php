<?php

namespace App\Actions\Identity;

use App\Models\Company;
use App\Models\Permission;
use App\Models\Role;
use App\Models\User;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;

class CreateCompanyForUser
{
    /**
     * Create a company and make its creator an administrator in one transaction.
     *
     * @param array<string, mixed> $attributes
     */
    public function handle(array $attributes, User $creator): Company
    {
        if (! $creator->is_active) {
            throw new AuthorizationException('حساب غیرفعال اجازه ایجاد شرکت ندارد.');
        }

        $hasMemberships = DB::table('company_user')
            ->where('user_id', $creator->id)
            ->exists();

        if ($hasMemberships && (
            $creator->currentCompanyId() === null
            || ! $creator->hasPermission('companies.manage')
        )) {
            throw new AuthorizationException('فقط مدیر شرکت جاری می‌تواند شرکت جدید ایجاد کند.');
        }

        $attributes = Validator::make($attributes, [
            'name' => ['required', 'string', 'max:255'],
            'slug' => ['required', 'string', 'max:255', 'regex:/^[a-z0-9]+(?:-[a-z0-9]+)*$/', 'unique:companies,slug'],
            'national_id' => ['nullable', 'string', 'max:50'],
            'registration_number' => ['nullable', 'string', 'max:50'],
            'phone' => ['nullable', 'string', 'max:50'],
            'email' => ['nullable', 'email', 'max:255'],
            'address' => ['nullable', 'string', 'max:2000'],
            'is_active' => ['required', 'boolean'],
        ])->validate();

        $company = DB::transaction(function () use ($attributes, $creator): Company {
            $lockedCreator = User::query()
                ->whereKey($creator->id)
                ->lockForUpdate()
                ->firstOrFail();

            $company = Company::query()->create($attributes);

            $adminRole = Role::query()->create([
                'company_id' => $company->id,
                'name' => 'admin',
                'label' => 'مدیر شرکت',
                'is_system' => true,
                'inherit_permissions' => false,
            ]);

            $adminRole->permissions()->sync(Permission::query()->pluck('id'));

            // Preserve the default flag even when its company is inactive or soft-deleted.
            $hasDefaultCompany = DB::table('company_user')
                ->where('user_id', $lockedCreator->id)
                ->where('is_default', true)
                ->exists();

            $lockedCreator->companies()->attach($company->id, [
                'role_id' => $adminRole->id,
                'is_default' => ! $hasDefaultCompany,
            ]);

            $membershipId = DB::table('company_user')
                ->where('company_id', $company->id)
                ->where('user_id', $lockedCreator->id)
                ->value('id');

            DB::table('company_user_role')->insert([
                'company_user_id' => $membershipId,
                'role_id' => $adminRole->id,
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            $now = now();

            DB::table('audit_events')->insert([
                'company_id' => $company->id,
                'auditable_type' => $company->getMorphClass(),
                'auditable_id' => $company->id,
                'actor_user_id' => $lockedCreator->id,
                'event' => 'created',
                'old_values' => json_encode([], JSON_THROW_ON_ERROR | JSON_UNESCAPED_UNICODE),
                'new_values' => json_encode([
                    'name' => $company->name,
                    'slug' => $company->slug,
                    'is_active' => (bool) $company->is_active,
                ], JSON_THROW_ON_ERROR | JSON_UNESCAPED_UNICODE),
                'metadata' => json_encode(['source' => 'company_creation'], JSON_THROW_ON_ERROR),
                'occurred_at' => $now,
                'created_at' => $now,
                'updated_at' => $now,
            ]);

            return $company;
        });

        session(['current_company_id' => $company->id]);

        return $company;
    }
}
