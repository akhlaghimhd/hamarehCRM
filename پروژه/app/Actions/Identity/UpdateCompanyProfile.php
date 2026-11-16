<?php

namespace App\Actions\Identity;

use App\Models\AuditEvent;
use App\Models\Company;
use App\Models\User;
use App\Support\CompanyContext;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;

class UpdateCompanyProfile
{
    public function handle(Company $record, array $data, User $actor): Company
    {
        if (! $actor->is_active || ! $actor->hasPermission('companies.manage')) {
            throw new AuthorizationException('اجازه ویرایش اطلاعات شرکت را ندارید.');
        }

        $companyId = app(CompanyContext::class)->requireCompanyId($actor);

        if ($companyId !== (int) $record->getKey()) {
            throw new AuthorizationException('ویرایش شرکتی خارج از فضای کاری جاری مجاز نیست.');
        }

        $validated = Validator::make($data, [
            'name' => ['required', 'string', 'max:255'],
            'slug' => ['required', 'string', 'max:255', 'regex:/^[a-z0-9]+(?:-[a-z0-9]+)*$/', Rule::unique('companies', 'slug')->ignore($record->getKey())],
            'national_id' => ['nullable', 'string', 'max:50'],
            'registration_number' => ['nullable', 'string', 'max:50'],
            'phone' => ['nullable', 'string', 'max:50'],
            'email' => ['nullable', 'email', 'max:255'],
            'address' => ['nullable', 'string', 'max:2000'],
            'is_active' => ['required', 'boolean'],
        ])->validate();

        return DB::transaction(function () use ($record, $validated, $actor, $companyId): Company {
            $company = Company::query()
                ->whereKey($record->getKey())
                ->lockForUpdate()
                ->firstOrFail();

            if (
                (int) $company->getKey() !== $companyId
                || ! $company->is_active
                || (int) $actor->currentCompanyId() !== $companyId
            ) {
                throw new AuthorizationException('شرکت جاری دیگر فعال یا در دسترس نیست.');
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

            // Audit before changing is_active while the current company context is valid.
            AuditEvent::query()->create([
                'company_id' => $companyId,
                'auditable_type' => $company->getMorphClass(),
                'auditable_id' => $company->getKey(),
                'actor_user_id' => $actor->id,
                'event' => 'updated',
                'old_values' => $oldValues,
                'new_values' => $newValues,
                'metadata' => ['source' => 'company_profile'],
                'occurred_at' => now(),
            ]);

            $company->save();

            return $company->refresh();
        });
    }
}
