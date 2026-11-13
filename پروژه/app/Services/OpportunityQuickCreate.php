<?php

namespace App\Services;

use App\Models\Activity;
use App\Models\Customer;
use App\Models\Opportunity;
use App\Models\OpportunityStage;
use Illuminate\Support\Facades\DB;

/**
 * ثبت سریع و هوشمند فرصت: مشتری، عنوان، مرحله، گام اول.
 */
class OpportunityQuickCreate
{
    /**
     * @param  array<string, mixed>  $data
     */
    public function create(array $data): Opportunity
    {
        return DB::transaction(function () use ($data) {
            $customer = $this->resolveCustomer($data);

            $stageId = $data['stage_id'] ?? OpportunityStage::query()
                ->where('code', 'lead')
                ->where('is_active', true)
                ->value('id');

            if (! $stageId) {
                $stageId = OpportunityStage::query()
                    ->where('is_active', true)
                    ->orderBy('sort_order')
                    ->value('id');
            }

            $org = trim((string) ($data['organization_name'] ?? ''));
            $title = trim((string) ($data['title'] ?? ''));
            if ($title === '') {
                $title = $org !== '' ? 'فرصت ' . $org : 'فرصت جدید';
            }

            $ownerId = (int) ($data['owner_user_id'] ?? auth()->id());
            $followId = (int) ($data['followup_user_id'] ?? $ownerId);

            $nextStep = trim((string) ($data['next_step'] ?? ''));
            if ($nextStep === '') {
                $nextStep = $this->suggestNextStep($data);
            }

            $due = $data['next_step_due'] ?? now()->addDays(3);

            /** @var Opportunity $opportunity */
            $opportunity = Opportunity::query()->create([
                'customer_id' => $customer->id,
                'stage_id' => $stageId,
                'owner_user_id' => $ownerId,
                'followup_user_id' => $followId,
                'title' => $title,
                'organization_name' => $org !== '' ? $org : $customer->name,
                'contact_name' => $data['contact_name'] ?? null,
                'contact_role' => $data['contact_role'] ?? null,
                'mobile' => $this->normalizePhone($data['mobile'] ?? null) ?: ($data['mobile'] ?? null),
                'phone' => $data['phone'] ?? null,
                'email' => $data['email'] ?? null,
                'product_interest' => $data['product_interest'] ?? null,
                'industry' => $data['industry'] ?? null,
                'status' => Opportunity::STATUS_ACTIVE,
                'amount' => $data['amount'] ?? null,
                'source' => $data['source'] ?? 'ثبت سریع',
                'notes' => $data['notes'] ?? null,
                'next_step' => $nextStep,
                'next_step_due' => $due,
            ]);

            Activity::query()->create([
                'company_id' => $opportunity->company_id,
                'opportunity_id' => $opportunity->id,
                'user_id' => $followId,
                'type' => Activity::TYPE_TASK,
                'subject' => $nextStep,
                'body' => 'گام اول — ساخته‌شده از ثبت سریع فرصت',
                'due_at' => $due,
                'is_done' => false,
            ]);

            return $opportunity->fresh(['customer', 'stage']);
        });
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function resolveCustomer(array $data): Customer
    {
        $name = trim((string) ($data['organization_name'] ?? ''));
        $rawPhone = $data['mobile'] ?? ($data['phone'] ?? null);
        $phone = $this->normalizePhone($rawPhone);
        $email = filled($data['email'] ?? null) ? trim((string) $data['email']) : null;

        $existing = null;

        if ($phone) {
            $existing = Customer::query()
                ->where(function ($q) use ($phone, $rawPhone) {
                    $q->where('phone', $phone);
                    if ($rawPhone && $rawPhone !== $phone) {
                        $q->orWhere('phone', $rawPhone);
                    }
                    // ارقام فارسی/عربی نرمال‌شده در مقایسه ساده
                    $digits = preg_replace('/\D+/', '', (string) $phone);
                    if ($digits !== '') {
                        $q->orWhereRaw(
                            "REPLACE(REPLACE(REPLACE(REPLACE(phone, ' ', ''), '-', ''), '+', ''), '۰', '0') LIKE ?",
                            ['%' . $digits . '%']
                        );
                    }
                })
                ->first();
        }

        if (! $existing && $name !== '') {
            $existing = Customer::query()->where('name', $name)->first();
        }

        if ($existing) {
            $existing->fill(array_filter([
                'phone' => $phone ?: ($rawPhone ?: $existing->phone),
                'email' => $email ?: $existing->email,
                'address' => $data['address'] ?? $existing->address,
                'name' => $name !== '' ? $name : $existing->name,
            ], fn ($v) => $v !== null && $v !== ''));
            $existing->save();

            return $existing;
        }

        return Customer::query()->create([
            'type' => Customer::TYPE_ORGANIZATION,
            'name' => $name !== '' ? $name : 'مشتری بدون نام',
            'phone' => $phone ?: $rawPhone,
            'email' => $email,
            'address' => $data['address'] ?? null,
            'is_active' => true,
            'notes' => 'ساخته‌شده از ثبت سریع فرصت',
        ]);
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function suggestNextStep(array $data): string
    {
        $contact = trim((string) ($data['contact_name'] ?? ''));
        $product = $data['product_interest'] ?? null;

        $productLabel = Opportunity::productInterestOptions()[$product] ?? null;

        if ($contact !== '' && $productLabel) {
            return "تماس با {$contact} درباره {$productLabel}";
        }

        if ($contact !== '') {
            return "تماس پیگیری با {$contact}";
        }

        if ($productLabel) {
            return "هماهنگی معرفی {$productLabel}";
        }

        return 'تماس اولیه و شناسایی نیاز';
    }

    public function normalizePhone(mixed $phone): ?string
    {
        if ($phone === null || $phone === '') {
            return null;
        }

        $map = [
            '۰' => '0', '۱' => '1', '۲' => '2', '۳' => '3', '۴' => '4',
            '۵' => '5', '۶' => '6', '۷' => '7', '۸' => '8', '۹' => '9',
            '٠' => '0', '١' => '1', '٢' => '2', '٣' => '3', '٤' => '4',
            '٥' => '5', '٦' => '6', '٧' => '7', '٨' => '8', '٩' => '9',
        ];

        $s = strtr((string) $phone, $map);
        $s = preg_replace('/[\s\-()]/', '', $s) ?? $s;

        if (str_starts_with($s, '0098')) {
            $s = '0' . substr($s, 4);
        } elseif (str_starts_with($s, '+98')) {
            $s = '0' . substr($s, 3);
        } elseif (str_starts_with($s, '98') && strlen($s) >= 12) {
            $s = '0' . substr($s, 2);
        }

        return $s !== '' ? $s : null;
    }
}
