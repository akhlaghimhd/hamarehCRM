<?php

namespace App\Support;

use App\Models\AuditEvent;
use App\Models\CompanySetting;
use App\Models\User;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use InvalidArgumentException;
use RuntimeException;

class CompanySettings
{
    private const TYPES = ['string', 'integer', 'boolean', 'decimal', 'json'];

    public function get(int $companyId, string $key, mixed $default = null): mixed
    {
        $this->assertCompanyContext($companyId);

        $setting = CompanySetting::query()->where('key', $key)->first();

        if (! $setting) {
            return $default;
        }

        if ($setting->is_sensitive) {
            $this->assertCanManage();
        }

        return $this->decodeValue($setting);
    }

    public function set(
        int $companyId,
        string $key,
        string $type,
        mixed $value,
        array $validationRules = [],
        bool $sensitive = false,
    ): CompanySetting {
        $this->assertCompanyContext($companyId);
        $this->assertCanManage();
        $this->assertKey($key);

        if (! in_array($type, self::TYPES, true)) {
            throw new InvalidArgumentException('نوع تنظیم شرکت پشتیبانی نمی‌شود.');
        }

        $this->validateValue($type, $value, $validationRules);

        return DB::transaction(function () use ($companyId, $key, $type, $value, $validationRules, $sensitive) {
            $setting = CompanySetting::query()
                ->where('key', $key)
                ->lockForUpdate()
                ->first();

            $isNew = $setting === null;
            $oldValue = $setting ? $this->decodeValue($setting) : null;
            $oldType = $setting?->type;
            $oldSensitive = (bool) ($setting?->is_sensitive ?? false);

            $encoded = json_encode($value, JSON_THROW_ON_ERROR | JSON_UNESCAPED_UNICODE);
            $storedValue = $sensitive ? Crypt::encryptString($encoded) : $encoded;

            $setting ??= new CompanySetting(['company_id' => $companyId, 'key' => $key]);
            $setting->fill([
                'type' => $type,
                'value' => $storedValue,
                'validation_rules' => $validationRules ?: null,
                'is_sensitive' => $sensitive,
            ]);
            $setting->save();

            $newAuditValue = [
                'key' => $key,
                'type' => $type,
                'is_sensitive' => $sensitive,
                'value' => $sensitive ? '[REDACTED]' : $value,
            ];
            $oldAuditValue = $isNew ? null : [
                'key' => $key,
                'type' => $oldType,
                'is_sensitive' => $oldSensitive,
                'value' => $oldSensitive ? '[REDACTED]' : $oldValue,
            ];

            AuditEvent::query()->create([
                'company_id' => $companyId,
                'auditable_type' => $setting->getMorphClass(),
                'auditable_id' => $setting->getKey(),
                'actor_user_id' => Auth::id(),
                'event' => $isNew ? 'created' : 'updated',
                'old_values' => $oldAuditValue,
                'new_values' => $newAuditValue,
                'metadata' => ['source' => 'company_settings'],
                'occurred_at' => now(),
            ]);

            return $setting;
        });
    }

    public function forget(int $companyId, string $key): bool
    {
        $this->assertCompanyContext($companyId);
        $this->assertCanManage();

        return DB::transaction(function () use ($companyId, $key) {
            $setting = CompanySetting::query()->where('key', $key)->lockForUpdate()->first();

            if (! $setting) {
                return false;
            }

            $auditValue = [
                'key' => $setting->key,
                'type' => $setting->type,
                'is_sensitive' => $setting->is_sensitive,
                'value' => $setting->is_sensitive ? '[REDACTED]' : $this->decodeValue($setting),
            ];

            AuditEvent::query()->create([
                'company_id' => $companyId,
                'auditable_type' => $setting->getMorphClass(),
                'auditable_id' => $setting->getKey(),
                'actor_user_id' => Auth::id(),
                'event' => 'deleted',
                'old_values' => $auditValue,
                'new_values' => null,
                'metadata' => ['source' => 'company_settings'],
                'occurred_at' => now(),
            ]);

            return (bool) $setting->delete();
        });
    }

    private function assertCompanyContext(int $companyId): void
    {
        if ($companyId < 1 || app(CompanyContext::class)->requireCompanyId() !== $companyId) {
            throw new RuntimeException('تنظیمات فقط در زمینه شرکت جاری قابل دسترسی است.');
        }
    }

    private function assertCanManage(): void
    {
        $user = Auth::user();

        if (! $user instanceof User || ! $user->isCompanyAdmin()) {
            throw new AuthorizationException('فقط مدیر شرکت مجاز به مدیریت تنظیمات است.');
        }
    }

    private function assertKey(string $key): void
    {
        if (! preg_match('/^[a-z][a-z0-9_.-]{1,99}$/', $key)) {
            throw new InvalidArgumentException('کلید تنظیم شرکت معتبر نیست.');
        }
    }

    private function validateValue(string $type, mixed $value, array $rules): void
    {
        $typeRule = match ($type) {
            'string' => 'string',
            'integer' => 'integer',
            'boolean' => 'boolean',
            'decimal' => 'numeric',
            'json' => 'array',
        };

        Validator::make(
            ['value' => $value],
            ['value' => array_merge(['required', $typeRule], $rules)],
        )->validate();
    }

    private function decodeValue(CompanySetting $setting): mixed
    {
        $raw = $setting->is_sensitive
            ? Crypt::decryptString((string) $setting->value)
            : (string) $setting->value;

        return json_decode($raw, true, 512, JSON_THROW_ON_ERROR);
    }
}
