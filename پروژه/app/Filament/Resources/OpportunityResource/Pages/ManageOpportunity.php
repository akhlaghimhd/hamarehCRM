<?php

namespace App\Filament\Resources\OpportunityResource\Pages;

use App\Filament\Resources\OpportunityResource;
use App\Models\Activity;
use App\Models\Opportunity;
use App\Models\OpportunityStage;
use App\Models\User;
use App\Support\Fa;
use Filament\Actions\Action;
use Filament\Actions\DeleteAction;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\ViewRecord;
use Illuminate\Support\Carbon;

class ManageOpportunity extends ViewRecord
{
    protected static string $resource = OpportunityResource::class;

    protected static ?string $title = 'میزکار فرصت';

    protected string $view = 'filament.resources.opportunity-resource.pages.manage-opportunity';

    public function getTitle(): string
    {
        return $this->record->title ?: ($this->record->organization_name ?: 'فرصت');
    }

    public function getHeading(): string
    {
        return $this->getTitle();
    }

    public function getBreadcrumbs(): array
    {
        return [
            OpportunityResource::getUrl() => OpportunityResource::getBreadcrumb(),
            OpportunityResource::getUrl('view', ['record' => $this->record]) => $this->getTitle(),
        ];
    }

    public function getSubheading(): ?string
    {
        $org = $this->record->organization_name;
        $contact = $this->record->contact_name;
        $parts = array_filter([$org, $contact]);

        return $parts ? implode(' · ', $parts) : null;
    }

    protected function getHeaderActions(): array
    {
        return [
            Action::make('editInfo')
                ->label('ویرایش اطلاعات')
                ->icon('heroicon-o-pencil-square')
                ->color('gray')
                ->url(fn () => OpportunityResource::getUrl('edit', ['record' => $this->record])),

            Action::make('assign')
                ->label('ارجاع')
                ->icon('heroicon-o-user-plus')
                ->color('gray')
                ->visible(fn () => OpportunityResource::canEdit($this->record))
                ->form([
                    Select::make('owner_user_id')
                        ->label('مالک جدید')
                        ->options(fn () => User::query()->where('is_active', true)->pluck('name', 'id'))
                        ->searchable()
                        ->required()
                        ->default(fn () => $this->record->owner_user_id),
                    Select::make('followup_user_id')
                        ->label('مسئول پیگیری')
                        ->options(fn () => User::query()->where('is_active', true)->pluck('name', 'id'))
                        ->searchable()
                        ->default(fn () => $this->record->followup_user_id),
                ])
                ->action(function (array $data): void {
                    $this->record->fill([
                        'owner_user_id' => $data['owner_user_id'],
                        'followup_user_id' => $data['followup_user_id'] ?? $data['owner_user_id'],
                    ])->save();

                    Notification::make()
                        ->title('ارجاع انجام شد')
                        ->success()
                        ->send();
                }),

            DeleteAction::make()
                ->label('حذف فرصت')
                ->visible(fn () => OpportunityResource::canDelete($this->record)),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    protected function getViewData(): array
    {
        /** @var Opportunity $record */
        $record = $this->record->loadMissing(['stage', 'owner', 'customer', 'activities.user']);

        $activities = $record->activities()
            ->with('user')
            ->orderByDesc('due_at')
            ->orderByDesc('id')
            ->get();

        $due = $record->next_step_due;
        $isOverdue = $due && Carbon::parse($due)->startOfDay()->lt(now()->startOfDay())
            && filled($record->next_step);

        return [
            'opportunity' => $record,
            'activities' => $activities,
            'isOverdue' => $isOverdue,
            'dueLabel' => $due ? Fa::dateTime($due, 'Y/m/d') : null,
            'statusLabel' => Opportunity::statusOptions()[$record->status] ?? ($record->status ?: '—'),
            'stageLabel' => $record->stage?->name ?? '—',
            'productLabel' => $record->productInterestLabel(),
            'ownerLabel' => $record->owner?->name ?? '—',
            'codeLabel' => 'OPP-' . str_pad((string) $record->id, 3, '0', STR_PAD_LEFT),
        ];
    }

    public function logActivityAction(): Action
    {
        return Action::make('logActivity')
            ->label('ثبت فعالیت')
            ->icon('heroicon-o-plus')
            ->color('primary')
            ->modalHeading('ثبت فعالیت')
            ->modalDescription('رویداد انجام‌شده (تماس، جلسه، …) را ثبت کنید.')
            ->form([
                Select::make('type')
                    ->label('نوع')
                    ->options(Activity::typeOptions())
                    ->required()
                    ->native(false),
                DatePicker::make('due_at')
                    ->label('تاریخ')
                    ->default(now())
                    ->required()
                    ->native(false),
                Textarea::make('subject')
                    ->label('خلاصه عملکرد')
                    ->placeholder('چه گفتید / چه شد؟')
                    ->required()
                    ->rows(3),
                Textarea::make('body')
                    ->label('نتیجه / واکنش (اختیاری)')
                    ->placeholder('واکنش مشتری یا خروجی ملموس')
                    ->rows(2),
            ])
            ->action(function (array $data): void {
                Activity::query()->create([
                    'company_id' => $this->record->company_id,
                    'opportunity_id' => $this->record->id,
                    'user_id' => auth()->id(),
                    'type' => $data['type'],
                    'subject' => $data['subject'],
                    'body' => $data['body'] ?? null,
                    'due_at' => $data['due_at'] ?? now(),
                    'is_done' => true,
                    'completed_at' => now(),
                ]);

                $this->record->touch();

                Notification::make()
                    ->title('فعالیت ثبت شد')
                    ->success()
                    ->send();
            });
    }

    public function closeStepAction(): Action
    {
        return Action::make('closeStep')
            ->label('بستن گام')
            ->icon('heroicon-o-check-circle')
            ->color('success')
            ->modalHeading('بستن گام جاری')
            ->modalDescription('نتیجهٔ این گام الزامی است. در صورت نیاز مرحله/وضعیت فرصت را هم عوض کنید.')
            ->form([
                Textarea::make('result')
                    ->label('نتیجه این گام')
                    ->placeholder('چه شد؟ خروجی ملموس این گام چه بود؟')
                    ->required()
                    ->rows(3),
                TextInput::make('next_step')
                    ->label('گام بعدی (اختیاری)')
                    ->placeholder('اگر پیگیری ادامه دارد، گام بعد را بنویسید'),
                DatePicker::make('next_step_due')
                    ->label('مهلت گام بعد')
                    ->native(false)
                    ->default(now()->addDays(3)),
                Select::make('stage_id')
                    ->label('مرحله قیف')
                    ->options(fn () => OpportunityStage::query()
                        ->where('is_active', true)
                        ->orderBy('sort_order')
                        ->pluck('name', 'id'))
                    ->default(fn () => $this->record->stage_id)
                    ->native(false),
                Select::make('status')
                    ->label('وضعیت')
                    ->options(Opportunity::statusOptions())
                    ->default(fn () => $this->record->status)
                    ->native(false),
            ])
            ->action(function (array $data): void {
                $result = trim((string) ($data['result'] ?? ''));

                Activity::query()->create([
                    'company_id' => $this->record->company_id,
                    'opportunity_id' => $this->record->id,
                    'user_id' => auth()->id(),
                    'type' => Activity::TYPE_NOTE,
                    'subject' => 'بستن گام: ' . \Illuminate\Support\Str::limit((string) $this->record->next_step, 80),
                    'body' => $result,
                    'due_at' => now(),
                    'is_done' => true,
                    'completed_at' => now(),
                ]);

                $next = trim((string) ($data['next_step'] ?? ''));
                $this->record->next_step = $next !== '' ? $next : null;
                $this->record->next_step_due = $next !== ''
                    ? ($data['next_step_due'] ?? now()->addDays(3))
                    : null;

                if (! empty($data['stage_id'])) {
                    $this->record->stage_id = $data['stage_id'];
                }
                if (! empty($data['status'])) {
                    $this->record->status = $data['status'];
                }

                $this->record->save();

                Notification::make()
                    ->title('گام بسته شد')
                    ->body($next !== '' ? 'گام بعدی ثبت شد.' : 'گام جاری بدون گام بعدی بسته شد.')
                    ->success()
                    ->send();
            });
    }

    public function clearStepAction(): Action
    {
        return Action::make('clearStep')
            ->label('حذف گام')
            ->icon('heroicon-o-trash')
            ->color('danger')
            ->requiresConfirmation()
            ->modalHeading('حذف گام جاری؟')
            ->modalDescription('فقط متن گام و مهلت پاک می‌شود؛ فعالیت‌های ثبت‌شده می‌مانند.')
            ->action(function (): void {
                $this->record->next_step = null;
                $this->record->next_step_due = null;
                $this->record->save();

                Notification::make()
                    ->title('گام حذف شد')
                    ->success()
                    ->send();
            });
    }

    public function setStepAction(): Action
    {
        return Action::make('setStep')
            ->label('تعریف گام')
            ->icon('heroicon-o-flag')
            ->color('primary')
            ->form([
                Textarea::make('next_step')
                    ->label('گام بعدی')
                    ->required()
                    ->rows(2),
                DatePicker::make('next_step_due')
                    ->label('مهلت')
                    ->native(false)
                    ->default(now()->addDays(3))
                    ->required(),
            ])
            ->action(function (array $data): void {
                $this->record->next_step = $data['next_step'];
                $this->record->next_step_due = $data['next_step_due'];
                $this->record->save();

                Activity::query()->create([
                    'company_id' => $this->record->company_id,
                    'opportunity_id' => $this->record->id,
                    'user_id' => auth()->id(),
                    'type' => Activity::TYPE_TASK,
                    'subject' => $data['next_step'],
                    'body' => 'گام جدید روی فرصت تعریف شد',
                    'due_at' => $data['next_step_due'],
                    'is_done' => false,
                ]);

                Notification::make()
                    ->title('گام تعریف شد')
                    ->success()
                    ->send();
            });
    }
}
