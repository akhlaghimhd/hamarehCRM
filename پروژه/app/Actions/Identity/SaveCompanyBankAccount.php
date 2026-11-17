<?php

namespace App\Actions\Identity;

use App\Models\CompanyBankAccount;
use App\Models\CompanyBankAccountVersion;
use App\Models\CompanyFinancialIdentity;
use App\Models\User;
use App\Support\CompanyMembershipAccess;
use App\Support\Fa;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;

class SaveCompanyBankAccount
{
    public function handle(?CompanyBankAccount $record, CompanyFinancialIdentity $identity, array $data, User $actor): CompanyBankAccount
    {
        if (! $actor->is_active || (! $actor->isPlatformOwner() && ! app(CompanyMembershipAccess::class)->canManage($actor, (int) $identity->company_id))) {
            throw new AuthorizationException('اجازه مدیریت حساب دریافت این شرکت را ندارید.');
        }

        if (! $identity->is_active) {
            throw new AuthorizationException('برای هویت مالی غیرفعال نمی‌توان حساب جدید یا ویرایش‌شده ثبت کرد.');
        }

        if ($record && ((int) $record->company_id !== (int) $identity->company_id || (int) $record->company_financial_identity_id !== (int) $identity->id)) {
            throw new AuthorizationException('حساب بانکی به این هویت مالی تعلق ندارد.');
        }

        foreach (['account_number', 'iban', 'card_number'] as $field) {
            $value = trim((string) ($data[$field] ?? ''));
            $data[$field] = $value === '' ? null : Fa::toEnglishDigits($value);
        }

        $validated = Validator::make($data, [
            'label' => ['nullable', 'string', 'max:100'],
            'account_holder' => ['required', 'string', 'max:255'],
            'bank_name' => ['required', 'string', 'max:100'],
            'account_number' => ['nullable', 'string', 'max:64', 'required_without_all:iban,card_number'],
            'iban' => ['nullable', 'string', 'max:64', 'required_without_all:account_number,card_number'],
            'card_number' => ['nullable', 'string', 'max:32', 'required_without_all:account_number,iban'],
            'branch' => ['nullable', 'string', 'max:255'],
            'is_default' => ['sometimes', 'boolean'],
            'is_active' => ['sometimes', 'boolean'],
        ], [
            'account_holder.required' => 'نام صاحب حساب الزامی است.',
            'bank_name.required' => 'نام بانک الزامی است.',
            'account_number.required_without_all' => 'حداقل یکی از شماره حساب، شبا یا شماره کارت را وارد کنید.',
            'iban.required_without_all' => 'حداقل یکی از شماره حساب، شبا یا شماره کارت را وارد کنید.',
            'card_number.required_without_all' => 'حداقل یکی از شماره حساب، شبا یا شماره کارت را وارد کنید.',
        ])->validate();

        if (($validated['is_default'] ?? false) && ! ($validated['is_active'] ?? $record?->is_active ?? true)) {
            throw ValidationException::withMessages(['is_default' => 'حساب غیرفعال نمی‌تواند حساب پیش‌فرض باشد.']);
        }

        return DB::transaction(function () use ($record, $identity, $validated, $actor): CompanyBankAccount {
            $lockedIdentity = CompanyFinancialIdentity::query()
                ->where('company_id', $identity->company_id)
                ->whereKey($identity->id)
                ->lockForUpdate()
                ->firstOrFail();

            if (! $actor->isPlatformOwner() && ! app(CompanyMembershipAccess::class)->canManage($actor, (int) $lockedIdentity->company_id)) {
                throw new AuthorizationException('دسترسی مدیریت حساب‌های این شرکت در همین فاصله لغو شده است.');
            }

            if (! $lockedIdentity->is_active) {
                throw new AuthorizationException('هویت مالی در همین فاصله غیرفعال شده است.');
            }

            $account = $record
                ? CompanyBankAccount::query()->where('company_id', $lockedIdentity->company_id)->where('company_financial_identity_id', $lockedIdentity->id)->whereKey($record->id)->lockForUpdate()->firstOrFail()
                : new CompanyBankAccount([
                    'company_id' => $lockedIdentity->company_id,
                    'company_financial_identity_id' => $lockedIdentity->id,
                    'version' => 0,
                    'is_active' => true,
                    'is_default' => false,
                ]);

            $account->fill($validated);
            if (! $account->is_active) {
                $account->is_default = false;
            }

            if ($account->is_default) {
                $siblings = CompanyBankAccount::query()
                    ->where('company_id', $lockedIdentity->company_id)
                    ->where('company_financial_identity_id', $lockedIdentity->id)
                    ->where('is_default', true)
                    ->when($account->exists, fn ($query) => $query->where('id', '!=', $account->id))
                    ->lockForUpdate()
                    ->get();

                foreach ($siblings as $sibling) {
                    $oldSnapshot = $sibling->only(['company_id', 'company_financial_identity_id', 'label', 'account_holder', 'bank_name', 'account_number', 'iban', 'card_number', 'branch', 'version', 'is_default', 'is_active']);
                    $sibling->forceFill(['is_default' => false, 'version' => $sibling->version + 1])->save();
                    CompanyBankAccountVersion::query()->create([
                        'company_id' => $lockedIdentity->company_id,
                        'company_bank_account_id' => $sibling->id,
                        'version' => $sibling->version,
                        'snapshot' => $sibling->only(['company_id', 'company_financial_identity_id', 'label', 'account_holder', 'bank_name', 'account_number', 'iban', 'card_number', 'branch', 'version', 'is_default', 'is_active']),
                        'created_by_user_id' => $actor->id,
                    ]);
                    $now = now();
                    DB::table('audit_events')->insert([
                        'company_id' => $lockedIdentity->company_id,
                        'auditable_type' => $sibling->getMorphClass(),
                        'auditable_id' => $sibling->id,
                        'actor_user_id' => $actor->id,
                        'event' => 'bank_account_default_cleared',
                        'old_values' => json_encode($oldSnapshot, JSON_THROW_ON_ERROR | JSON_UNESCAPED_UNICODE),
                        'new_values' => json_encode($sibling->only(['company_id', 'company_financial_identity_id', 'label', 'account_holder', 'bank_name', 'account_number', 'iban', 'card_number', 'branch', 'version', 'is_default', 'is_active']), JSON_THROW_ON_ERROR | JSON_UNESCAPED_UNICODE),
                        'metadata' => json_encode(['financial_identity_id' => $lockedIdentity->id], JSON_THROW_ON_ERROR),
                        'occurred_at' => $now,
                        'created_at' => $now,
                        'updated_at' => $now,
                    ]);
                }
            }

            $account->version = ((int) $account->version) + 1;
            $account->save();

            $snapshot = $account->only(['company_id', 'company_financial_identity_id', 'label', 'account_holder', 'bank_name', 'account_number', 'iban', 'card_number', 'branch', 'version', 'is_default', 'is_active']);

            CompanyBankAccountVersion::query()->create([
                'company_id' => $lockedIdentity->company_id,
                'company_bank_account_id' => $account->id,
                'version' => $account->version,
                'snapshot' => $snapshot,
                'created_by_user_id' => $actor->id,
            ]);

            $now = now();
            DB::table('audit_events')->insert([
                'company_id' => $lockedIdentity->company_id,
                'auditable_type' => $account->getMorphClass(),
                'auditable_id' => $account->id,
                'actor_user_id' => $actor->id,
                'event' => $record ? 'bank_account_updated' : 'bank_account_created',
                'old_values' => json_encode([], JSON_THROW_ON_ERROR),
                'new_values' => json_encode($snapshot, JSON_THROW_ON_ERROR | JSON_UNESCAPED_UNICODE),
                'metadata' => json_encode(['financial_identity_id' => $lockedIdentity->id, 'version' => $account->version], JSON_THROW_ON_ERROR),
                'occurred_at' => $now,
                'created_at' => $now,
                'updated_at' => $now,
            ]);

            return $account->refresh();
        });
    }
}
