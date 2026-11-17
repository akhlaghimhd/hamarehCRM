<?php

namespace App\Actions\Identity;

use App\Models\Company;
use App\Models\Permission;
use App\Models\Role;
use App\Models\User;
use App\Support\Fa;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;

class CreateCompanyForUser
{
    /**
     * Create a company and make its creator an administrator in one transaction.
     *
     * @param array<string, mixed> $attributes
     */
    public function handle(array $attributes, User $creator): Company
    {
        $attributes['national_id'] = Fa::toEnglishDigits($attributes['national_id'] ?? '');
        $attributes['phone'] = Fa::toEnglishDigits($attributes['phone'] ?? '');

        if (blank($attributes['slug'] ?? null)) {
            $slug = Str::slug((string) ($attributes['name'] ?? ''));
            $attributes['slug'] = $slug !== '' ? $slug : 'company-'.Str::lower(Str::random(8));
        }

        $baseSlug = $attributes['slug'];
        $suffix = 1;

        while (DB::table('companies')->where('slug', $attributes['slug'])->exists()) {
            $attributes['slug'] = $baseSlug.'-'.$suffix++;
        }

        // Newly created companies are active by default. Status is managed from the table.
        $attributes['is_active'] = true;

        $attributes = Validator::make($attributes, [
            'name' => ['required', 'string', 'max:255'],
            'slug' => ['required', 'string', 'max:255', 'regex:/^[a-z0-9]+(?:-[a-z0-9]+)*$/', 'unique:companies,slug'],
            'national_id' => ['nullable', 'regex:/^\d{10}$/'],
            'registration_number' => ['nullable', 'string', 'max:50'],
            'phone' => ['nullable', 'regex:/^\d{8,11}$/'],
            'email' => ['nullable', 'email', 'max:255'],
            'address' => ['nullable', 'string', 'max:2000'],
            'is_active' => ['required', 'boolean'],
        ], [
            'name.required' => 'وارد کردن نام شرکت الزامی است.',
            'name.max' => 'نام شرکت نباید بیشتر از ۲۵۵ نویسه باشد.',
            'slug.required' => 'شناسه سیستمی شرکت تولید نشده است.',
            'slug.regex' => 'شناسه سیستمی فقط می‌تواند شامل حروف انگلیسی کوچک، عدد و خط تیره باشد.',
            'slug.unique' => 'این شناسه سیستمی قبلاً ثبت شده است.',
            'national_id.regex' => 'شناسه ملی باید دقیقاً ۱۰ رقم باشد.',
            'phone.regex' => 'شماره تلفن باید بین ۸ تا ۱۱ رقم باشد.',
            'email.email' => 'قالب ایمیل معتبر نیست.',
            'email.max' => 'ایمیل بیش از حد مجاز طولانی است.',
            'address.max' => 'نشانی نباید بیشتر از ۲۰۰۰ نویسه باشد.',
        ], [
            'name' => 'نام شرکت',
            'slug' => 'شناسه سیستمی',
            'national_id' => 'شناسه ملی',
            'phone' => 'تلفن',
            'email' => 'ایمیل',
            'address' => 'نشانی',
        ])->validate();

        $company = DB::transaction(function () use ($attributes, $creator): Company {
            $lockedCreator = User::query()
                ->whereKey($creator->id)
                ->lockForUpdate()
                ->firstOrFail();

            if (! $lockedCreator->is_active) {
                throw new AuthorizationException('حساب کاربری غیرفعال اجازه ایجاد شرکت ندارد.');
            }

            $hasMembership = DB::table('company_user')
                ->where('user_id', $lockedCreator->id)
                ->exists();

            if ($hasMembership && ! $lockedCreator->isCompanyAdmin()) {
                throw new AuthorizationException('فقط مدیر شرکت جاری می‌تواند شرکت جدید ایجاد کند.');
            }

            $company = Company::query()->create($attributes);

            $adminRole = Role::query()->create([
                'company_id' => $company->id,
                'name' => 'admin',
                'label' => 'مدیر شرکت',
                'is_system' => true,
                'inherit_permissions' => false,
            ]);

            $adminRole->permissions()->sync(Permission::query()->pluck('id'));

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
