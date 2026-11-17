<?php

namespace App\Actions\Identity;

use App\Models\Company;
use App\Models\User;
use App\Support\CompanyMembershipAccess;
use App\Support\Fa;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;

class UpdateCompanyProfile
{
    public function handle(Company $record, array $data, User $actor): Company
    {
        if (! app(CompanyMembershipAccess::class)->isAdmin($actor, (int) $record->id)) {
            throw new AuthorizationException('فقط مدیر همان شرکت اجازه ویرایش اطلاعات آن را دارد.');
        }

        $data['national_id'] = Fa::toEnglishDigits($data['national_id'] ?? '');
        $data['phone'] = Fa::toEnglishDigits($data['phone'] ?? '');

        $validated = Validator::make($data, [
            'name' => ['required', 'string', 'max:255'],
            'slug' => ['required', 'string', 'max:255', 'regex:/^[a-z0-9]+(?:-[a-z0-9]+)*$/', Rule::unique('companies', 'slug')->ignore($record->getKey())],
            'national_id' => ['nullable', 'regex:/^\d{10}$/'],
            'registration_number' => ['nullable', 'string', 'max:50'],
            'phone' => ['nullable', 'regex:/^\d{8,11}$/'],
            'email' => ['nullable', 'email', 'max:255'],
            'address' => ['nullable', 'string', 'max:2000'],
        ], [
            'name.required' => 'وارد کردن نام شرکت الزامی است.',
            'name.max' => 'نام شرکت نباید بیشتر از ۲۵۵ نویسه باشد.',
            'slug.required' => 'شناسه سیستمی شرکت الزامی است.',
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

        return DB::transaction(function () use ($record, $validated, $actor): Company {
            $company = Company::query()
                ->whereKey($record->getKey())
                ->lockForUpdate()
                ->firstOrFail();

            if (! $company->is_active || ! app(CompanyMembershipAccess::class)->isAdmin($actor, (int) $company->id)) {
                throw new AuthorizationException('شرکت غیرفعال است یا دسترسی مدیریت آن را ندارید.');
            }

            $company->fill($validated);
            $dirtyKeys = array_keys($company->getDirty());

            if ($dirtyKeys === []) {
                return $company;
            }

            $oldValues = [];
            $newValues = [];

            foreach ($dirtyKeys as $key) {
                $oldValues[$key] = $company->getOriginal($key);
                $newValues[$key] = $company->getAttribute($key);
            }

            $now = now();

            DB::table('audit_events')->insert([
                'company_id' => $company->id,
                'auditable_type' => $company->getMorphClass(),
                'auditable_id' => $company->getKey(),
                'actor_user_id' => $actor->id,
                'event' => 'updated',
                'old_values' => json_encode($oldValues, JSON_THROW_ON_ERROR | JSON_UNESCAPED_UNICODE),
                'new_values' => json_encode($newValues, JSON_THROW_ON_ERROR | JSON_UNESCAPED_UNICODE),
                'metadata' => json_encode(['source' => 'company_profile'], JSON_THROW_ON_ERROR),
                'occurred_at' => $now,
                'created_at' => $now,
                'updated_at' => $now,
            ]);

            $company->save();

            return $company->refresh();
        });
    }
}
