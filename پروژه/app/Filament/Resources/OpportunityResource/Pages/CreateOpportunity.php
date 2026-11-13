<?php

namespace App\Filament\Resources\OpportunityResource\Pages;

use App\Filament\Resources\OpportunityResource;
use App\Models\Activity;
use App\Models\Opportunity;
use App\Services\OpportunityQuickCreate;
use Filament\Actions\Action;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Database\Eloquent\Model;

class CreateOpportunity extends CreateRecord
{
    protected static string $resource = OpportunityResource::class;

    protected static ?string $title = 'فرصت جدید (فرم کامل)';

    protected function getCreatedNotificationTitle(): ?string
    {
        return 'فرصت ثبت شد';
    }

    protected function getHeaderActions(): array
    {
        return [
            Action::make('quick')
                ->label('ثبت سریع')
                ->icon('heroicon-o-bolt')
                ->url(OpportunityResource::getUrl('quick-create'))
                ->color('gray'),
        ];
    }

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $data['owner_user_id'] = $data['owner_user_id'] ?? auth()->id();
        $data['followup_user_id'] = $data['followup_user_id'] ?? $data['owner_user_id'];
        $data['status'] = $data['status'] ?? Opportunity::STATUS_ACTIVE;

        if (blank($data['title'] ?? null) && filled($data['organization_name'] ?? null)) {
            $data['title'] = 'فرصت ' . $data['organization_name'];
        }

        if (blank($data['next_step'] ?? null)) {
            $data['next_step'] = app(OpportunityQuickCreate::class)->suggestNextStep($data);
        }

        $customer = app(OpportunityQuickCreate::class)->resolveCustomer($data);
        $data['customer_id'] = $customer->id;

        if (filled($data['mobile'] ?? null)) {
            $normalized = app(OpportunityQuickCreate::class)->normalizePhone($data['mobile']);
            if ($normalized) {
                $data['mobile'] = $normalized;
            }
        }

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
