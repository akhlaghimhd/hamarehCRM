<?php

namespace App\Actions\Identity;

use App\Models\Company;
use App\Models\CompanyFinancialIdentity;
use App\Models\CompanyFinancialIdentityVersion;
use App\Models\Permission;
use App\Models\PlatformOwner;
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
     * Create a company and its initial administrator atomically; only platform owners may do so.
     *
     * @param array<string, mixed> $attributes
     */
    public function handle(array $attributes, User $creator): Company
    {
        if (! $creator->is_active || ! $creator->isPlatformOwner()) {
            throw new AuthorizationException('فقط مالک پلتفرم اجازه ایجاد شرکت دارد.');
        }

        $manager = [
            'name' => trim((string) ($attributes['admin_name'] ?? '')),
            'username' => trim((string) ($attributes['admin_username'] ?? '')),
            'email' => trim((string) ($attributes['admin_email'] ?? '')),
            'password' => (string) ($attributes['admin_password'] ?? ''),
            'password_confirmation' => (string) ($attributes['admin_password_confirmation'] ?? ''),
        ];
        unset($attributes['admin_name'], $attributes['admin_username'], $attributes['admin_email'], $attributes['admin_password'], $attributes['admin_password_confirmation']);
        foreach (['national_id', 'phone', 'registration_number'] as $field) {
            $value = trim((string) ($attributes[$field] ?? ''));
            $attributes[$field] = $value === '' ? null : Fa::toEnglishDigits($value);
        }

        if (blank($attributes['slug'] ?? null)) {
            $slug = Str::slug((string) ($attributes['name'] ?? ''));
            $baseSlug = $slug !== '' ? $slug : 'company-'.Str::lower(Str::random(8));
            $attributes['slug'] = $baseSlug;
            $suffix = 1;

            while (DB::table('companies')->where('slug', $attributes['slug'])->exists()) {
                $attributes['slug'] = $baseSlug.'-'.$suffix++;
            }
        }

        // Newly created companies are active by default. Status is managed from the table.
        $attributes['is_active'] = true;

        $manager = Validator::make($manager, [
            'name' => ['required', 'string', 'max:255'],
            'username' => ['required', 'string', 'max:100', 'alpha_dash', 'unique:users,username'],
            'email' => ['required', 'email', 'max:255', 'unique:users,email'],
            'password' => ['required', 'string', 'min:8', 'same:password_confirmation'],
            'password_confirmation' => ['required', 'string'],
        ], [
            'name.required' => 'نام مدیر اولیه الزامی است.',
            'username.required' => 'نام کاربری مدیر اولیه الزامی است.',
            'username.unique' => 'این نام کاربری قبلاً ثبت شده است.',
            'email.required' => 'ایمیل مدیر اولیه الزامی است.',
            'email.unique' => 'این ایمیل قبلاً ثبت شده است.',
            'password.min' => 'گذرواژه مدیر اولیه باید حداقل ۸ نویسه باشد.',
            'password.same' => 'تکرار گذرواژه با گذرواژه یکسان نیست.',
        ])->validate();

        $attributes = Validator::make($attributes, [
            'name' => ['required', 'string', 'max:255'],
            'slug' => ['required', 'string', 'max:255', 'regex:/^[a-z0-9]+(?:-[a-z0-9]+)*$/', 'unique:companies,slug'],
            'national_id' => ['nullable', 'regex:/^\d{11}$/'],
            'registration_number' => ['nullable', 'string', 'max:50'],
            'phone' => ['nullable', 'regex:/^\d{8,11}$/'],
            'email' => ['nullable', 'email', 'max:255'],
            'address' => ['nullable', 'string', 'max:2000'],
            'website' => ['nullable', 'string', 'max:255'],
            'is_active' => ['required', 'boolean'],
        ], [
            'name.required' => 'وارد کردن نام شرکت الزامی است.',
            'name.max' => 'نام شرکت نباید بیشتر از ۲۵۵ نویسه باشد.',
            'slug.required' => 'شناسه سیستمی شرکت تولید نشده است.',
            'slug.max' => 'شناسه سیستمی بیش از حد مجاز طولانی است.',
            'slug.regex' => 'شناسه سیستمی فقط می‌تواند شامل حروف انگلیسی کوچک، عدد و خط تیره باشد.',
            'slug.unique' => 'این شناسه سیستمی قبلاً ثبت شده است.',
            'national_id.regex' => 'شناسه ملی شرکت باید دقیقاً ۱۱ رقم باشد.',
            'registration_number.max' => 'شماره ثبت بیش از حد مجاز طولانی است.',
            'phone.regex' => 'شماره تلفن باید بین ۸ تا ۱۱ رقم باشد.',
            'email.email' => 'قالب ایمیل معتبر نیست.',
            'email.max' => 'ایمیل بیش از حد مجاز طولانی است.',
            'address.max' => 'نشانی نباید بیشتر از ۲۰۰۰ نویسه باشد.',
            'website.max' => 'نشانی وب‌سایت نباید بیشتر از ۲۵۵ نویسه باشد.',
        ], [
            'name' => 'نام شرکت',
            'slug' => 'شناسه سیستمی',
            'national_id' => 'شناسه ملی',
            'phone' => 'تلفن',
            'email' => 'ایمیل',
            'address' => 'نشانی',
            'website' => 'وب‌سایت',
        ])->validate();

        $company = DB::transaction(function () use ($attributes, $creator, $manager): Company {
            $lockedCreator = User::query()
                ->whereKey($creator->id)
                ->lockForUpdate()
                ->firstOrFail();

            if (! $lockedCreator->is_active) {
                throw new AuthorizationException('حساب کاربری غیرفعال اجازه ایجاد شرکت ندارد.');
            }

            if (! PlatformOwner::query()->where('user_id', $lockedCreator->id)->exists()) {
                throw new AuthorizationException('فقط مالک پلتفرم اجازه ایجاد شرکت دارد.');
            }

            $company = Company::query()->create($attributes);

            foreach (['official', 'unofficial'] as $identityType) {
                $identity = CompanyFinancialIdentity::query()->create([
                    'company_id' => $company->id,
                    'type' => $identityType,
                    'legal_name' => $company->name,
                    'national_id' => $company->national_id,
                    'registration_number' => $company->registration_number,
                    'phone' => $company->phone,
                    'email' => $company->email,
                    'address' => $company->address,
                    'website' => $company->website,
                    'version' => 1,
                    'is_active' => true,
                ]);

                CompanyFinancialIdentityVersion::query()->create([
                    'company_id' => $company->id,
                    'company_financial_identity_id' => $identity->id,
                    'version' => 1,
                    'snapshot' => $identity->only(['company_id', 'type', 'legal_name', 'national_id', 'economic_code', 'registration_number', 'phone', 'email', 'postal_code', 'address', 'website', 'version', 'is_active']),
                    'created_by_user_id' => $lockedCreator->id,
                ]);
            }

            $initialAdmin = User::query()->create([
                'name' => $manager['name'],
                'username' => $manager['username'],
                'email' => $manager['email'],
                'password' => $manager['password'],
                'is_active' => true,
            ]);

            $adminRole = Role::query()->create([
                'company_id' => $company->id,
                'name' => 'admin',
                'label' => 'مدیر شرکت',
                'is_system' => true,
                'inherit_permissions' => false,
            ]);

            $adminRole->permissions()->sync(Permission::query()->pluck('id'));

            $initialAdmin->companies()->attach($company->id, [
                'role_id' => $adminRole->id,
                'is_default' => true,
            ]);

            $membershipId = DB::table('company_user')
                ->where('company_id', $company->id)
                ->where('user_id', $initialAdmin->id)
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

        return $company;
    }
}
