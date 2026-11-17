<?php

namespace App\Actions\Identity;

use App\Models\Company;
use App\Models\CompanyFinancialIdentity;
use App\Models\CompanyFinancialIdentityVersion;
use App\Models\User;
use App\Support\CompanyMembershipAccess;
use App\Support\Fa;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;

class SaveCompanyFinancialIdentity
{
    public function handle(?CompanyFinancialIdentity $record, Company $company, array $data, User $actor): CompanyFinancialIdentity
    {
        $this->authorize($actor, (int) $company->id);

        if ($record && (int) $record->company_id !== (int) $company->id) {
            throw new AuthorizationException('هویت مالی به شرکت انتخاب‌شده تعلق ندارد.');
        }

        foreach (['national_id', 'economic_code', 'registration_number', 'phone', 'postal_code'] as $field) {
            $value = trim((string) ($data[$field] ?? ''));
            $data[$field] = $value === '' ? null : Fa::toEnglishDigits($value);
        }

        $validated = Validator::make($data, [
            'type' => ['required', Rule::in(['official', 'unofficial']), Rule::unique('company_financial_identities', 'type')->where('company_id', $company->id)->ignore($record?->id)],
            'legal_name' => ['required', 'string', 'max:255'],
            'national_id' => ['nullable', 'string', 'max:32'],
            'economic_code' => ['nullable', 'string', 'max:64'],
            'registration_number' => ['nullable', 'string', 'max:64'],
            'phone' => ['nullable', 'string', 'max:32'],
            'email' => ['nullable', 'email', 'max:255'],
            'postal_code' => ['nullable', 'string', 'max:20'],
            'address' => ['nullable', 'string', 'max:2000'],
            'website' => ['nullable', 'string', 'max:255'],
            'is_active' => ['sometimes', 'boolean'],
        ], [
            'type.in' => 'نوع هویت مالی معتبر نیست.',
            'type.unique' => 'برای این شرکت از این نوع هویت مالی قبلاً ثبت شده است.',
            'legal_name.required' => 'نام حقوقی/صادرکننده الزامی است.',
        ])->validate();

        return DB::transaction(function () use ($record, $company, $validated, $actor): CompanyFinancialIdentity {
            $lockedCompany = Company::query()->whereKey($company->id)->lockForUpdate()->firstOrFail();
            $identity = $record
                ? CompanyFinancialIdentity::query()->where('company_id', $lockedCompany->id)->whereKey($record->id)->lockForUpdate()->firstOrFail()
                : new CompanyFinancialIdentity(['company_id' => $lockedCompany->id, 'version' => 0, 'is_active' => true]);

            $identity->fill($validated);

            if (! $identity->isDirty()) {
                return $identity;
            }

            $identity->version = ((int) $identity->version) + 1;
            $identity->save();

            $snapshot = $identity->only([
                'company_id', 'type', 'legal_name', 'national_id', 'economic_code',
                'registration_number', 'phone', 'email', 'postal_code', 'address',
                'website', 'version', 'is_active',
            ]);

            CompanyFinancialIdentityVersion::query()->create([
                'company_id' => $lockedCompany->id,
                'company_financial_identity_id' => $identity->id,
                'version' => $identity->version,
                'snapshot' => $snapshot,
                'created_by_user_id' => $actor->id,
            ]);

            $now = now();
            DB::table('audit_events')->insert([
                'company_id' => $lockedCompany->id,
                'auditable_type' => $identity->getMorphClass(),
                'auditable_id' => $identity->id,
                'actor_user_id' => $actor->id,
                'event' => $record ? 'financial_identity_updated' : 'financial_identity_created',
                'old_values' => json_encode([], JSON_THROW_ON_ERROR),
                'new_values' => json_encode($snapshot, JSON_THROW_ON_ERROR | JSON_UNESCAPED_UNICODE),
                'metadata' => json_encode(['version' => $identity->version], JSON_THROW_ON_ERROR),
                'occurred_at' => $now,
                'created_at' => $now,
                'updated_at' => $now,
            ]);

            return $identity->refresh();
        });
    }

    private function authorize(User $actor, int $companyId): void
    {
        if (! $actor->is_active || (! $actor->isPlatformOwner() && ! app(CompanyMembershipAccess::class)->canManage($actor, $companyId))) {
            throw new AuthorizationException('اجازه مدیریت هویت مالی این شرکت را ندارید.');
        }
    }
}
