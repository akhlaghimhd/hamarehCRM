<?php

namespace App\Filament\Resources\OpportunityResource\Pages;

use App\Filament\Resources\OpportunityResource;
use App\Models\Activity;
use App\Models\Customer;
use App\Models\Opportunity;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Database\Eloquent\Model;

class CreateOpportunity extends CreateRecord
{
    protected static string $resource = OpportunityResource::class;

    protected static ?string $title = 'فرصت جدید';

    protected function getCreatedNotificationTitle(): ?string
    {
        return 'فرصت ثبت شد';
    }

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $data['owner_user_id'] = $data['owner_user_id'] ?? auth()->id();
        $data['followup_user_id'] = $data['followup_user_id'] ?? $data['owner_user_id'];
        $data['status'] = $data['status'] ?? Opportunity::STATUS_ACTIVE;

        if (blank($data['title'] ?? null) && filled($data['organization_name'] ?? null)) {
            $data['title'] = 'فرصت ' . $data['organization_name'];
        }

        // مشتری را هوشمند پیدا/بساز
        $customer = $this->resolveCustomer($data);
        $data['customer_id'] = $customer->id;

        return $data;
    }

    protected function handleRecordCreation(array $data): Model
    {
        /** @var Opportunity $opportunity */
        $opportunity = parent::handleRecordCreation($data);

        $this->createFirstFollowUp($opportunity, $data);

        return $opportunity;
    }

    /**
     * @param  array<string, mixed>  $data
     */
    private function resolveCustomer(array $data): Customer
    {
        $name = trim((string) ($data['organization_name'] ?? ''));
        $phone = $data['mobile'] ?: ($data['phone'] ?? null);

        $query = Customer::query()->where('name', $name);

        if ($phone) {
            $query->where(function ($q) use ($phone) {
                $q->where('phone', $phone);
            });
        }

        $existing = $query->first();

        if ($existing) {
            $existing->fill(array_filter([
                'phone' => $phone ?: $existing->phone,
                'email' => $data['email'] ?? $existing->email,
                'address' => $data['address'] ?? $existing->address,
            ]));
            $existing->save();

            return $existing;
        }

        return Customer::query()->create([
            'type' => Customer::TYPE_ORGANIZATION,
            'name' => $name !== '' ? $name : 'مشتری بدون نام',
            'phone' => $phone,
            'email' => $data['email'] ?? null,
            'address' => $data['address'] ?? null,
            'is_active' => true,
            'notes' => 'ساخته‌شده از ثبت سریع فرصت',
        ]);
    }

    /**
     * @param  array<string, mixed>  $data
     */
    private function createFirstFollowUp(Opportunity $opportunity, array $data): void
    {
        $subject = trim((string) ($data['next_step'] ?? ''));

        if ($subject === '') {
            return;
        }

        Activity::query()->create([
            'company_id' => $opportunity->company_id,
            'opportunity_id' => $opportunity->id,
            'user_id' => $data['followup_user_id'] ?? $opportunity->owner_user_id ?? auth()->id(),
            'type' => Activity::TYPE_TASK,
            'subject' => $subject,
            'body' => 'گام اول پیگیری پس از ثبت فرصت',
            'due_at' => $data['next_step_due'] ?? now()->addDays(3),
            'is_done' => false,
        ]);
    }
}
