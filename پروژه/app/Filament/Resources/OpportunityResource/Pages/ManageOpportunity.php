<?php

namespace App\Filament\Resources\OpportunityResource\Pages;

use App\Filament\Resources\OpportunityResource;
use App\Models\Activity;
use App\Models\Opportunity;
use App\Models\OpportunityStage;
use App\Models\User;
use App\Support\Fa;
use Filament\Actions\Action;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\ViewRecord;
use Illuminate\Support\Carbon;
use Illuminate\Support\Str;

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

    public function hasBreadcrumbs(): bool
    {
        return true;
    }

    public function getBreadcrumbs(): array
    {
        $listLabel = OpportunityResource::getNavigationLabel()
            ?: OpportunityResource::getBreadcrumb()
            ?: 'فرصت‌ها';

        return [
            OpportunityResource::getUrl() => $listLabel,
        ];
    }

    public function getSubheading(): ?string
    {
        return null;
    }

    protected function getHeaderActions(): array
    {
        return [
            Action::make('refer')
                ->label('ارجاع')
                ->icon('heroicon-o-arrow-path-rounded-square')
                ->color('gray')
                ->visible(fn () => OpportunityResource::canEdit($this->record) && filled($this->record->next_step))
                ->modalHeading('ارجاع گام جاری')
                ->modalDescription('گام فعلی بسته می‌شود و برای نفر مقصد یک گام جدید باز می‌شود.')
                ->form([
                    Select::make('to_user_id')
                        ->label('ارجاع به')
                        ->options(fn () => User::query()->where('is_active', true)->pluck('name', 'id'))
                        ->searchable()
                        ->required()
                        ->native(false),
                    Textarea::make('close_result')
                        ->label('نتیجهٔ گام فعلی (قبل از ارجاع)')
                        ->required()
                        ->rows(3),
                    Textarea::make('new_step')
                        ->label('گام جدید برای نفر مقصد')
                        ->required()
                        ->rows(2),
                    DatePicker::make('new_step_due')
                        ->label('مهلت گام مقصد')
                        ->native(false)
                        ->default(now()->addDays(3))
                        ->required(),
                ])
                ->action(function (array $data): void {
                    $from = auth()->user();
                    $to = User::query()->find($data['to_user_id']);
                    if (! $to) {
                        Notification::make()->title('کاربر مقصد یافت نشد')->danger()->send();

                        return;
                    }

                    $closedSubject = (string) $this->record->next_step;

                    Activity::query()->create([
                        'company_id' => $this->record->company_id,
                        'opportunity_id' => $this->record->id,
                        'user_id' => $from?->id,
                        'type' => Activity::TYPE_NOTE,
                        'subject' => 'بستن گام: ' . Str::limit($closedSubject, 80),
                        'body' => trim((string) $data['close_result']),
                        'due_at' => now(),
                        'is_done' => true,
                        'completed_at' => now(),
                    ]);

                    Activity::query()->create([
                        'company_id' => $this->record->company_id,
                        'opportunity_id' => $this->record->id,
                        'user_id' => $from?->id,
                        'type' => Activity::TYPE_NOTE,
                        'subject' => 'ارجاع: از ' . ($from?->name ?? '—') . ' به ' . $to->name,
                        'body' => 'گام بسته شد و برای «' . $to->name . '» گام جدید تعریف شد.\n' . trim((string) $data['new_step']),
                        'due_at' => now(),
                        'is_done' => true,
                        'completed_at' => now(),
                    ]);

                    Activity::query()->create([
                        'company_id' => $this->record->company_id,
                        'opportunity_id' => $this->record->id,
                        'user_id' => $to->id,
                        'type' => Activity::TYPE_TASK,
                        'subject' => $data['new_step'],
                        'body' => 'گام ارجاع‌شده از طرف ' . ($from?->name ?? 'سیستم'),
                        'due_at' => $data['new_step_due'],
                        'is_done' => false,
                    ]);

                    $this->record->next_step = $data['new_step'];
                    $this->record->next_step_due = $data['new_step_due'];
                    $this->record->followup_user_id = $to->id;
                    $this->record->save();

                    Notification::make()
                        ->title('ارجاع انجام شد')
                        ->body('گام برای «' . $to->name . '» باز شد.')
                        ->success()
                        ->send();
                }),
        ];
    }

    protected function getViewData(): array
    {
        /** @var Opportunity $record */
        $record = $this->record->loadMissing(['stage', 'owner', 'followup', 'customer', 'activities.user']);

        $activities = $record->activities()
            ->with('user')
            ->orderByDesc('due_at')
            ->orderByDesc('id')
            ->get();

        $due = $record->next_step_due;
        $isOverdue = $due && Carbon::parse($due)->startOfDay()->lt(now()->startOfDay())
            && filled($record->next_step);

        $roleKey = $record->decision_role ?: $record->contact_role;
        $roleMap = Opportunity::decisionRoleOptions();
        $roleLabel = $roleMap[$roleKey ?? ''] ?? null;

        $isClosure = static function (Activity $a): bool {
            $s = (string) $a->subject;

            return str_starts_with($s, 'بستن گام:') || str_starts_with($s, 'ارجاع:');
        };

        $closedSteps = $activities->filter($isClosure)->values();
        $stepActivities = $activities->reject($isClosure)->values();

        $listLabel = OpportunityResource::getNavigationLabel()
            ?: OpportunityResource::getBreadcrumb()
            ?: 'فرصت‌ها';

        return [
            'opportunity' => $record,
            'activities' => $activities,
            'stepActivities' => $stepActivities,
            'closedSteps' => $closedSteps,
            'isOverdue' => $isOverdue,
            'dueLabel' => $due ? Fa::dateTime($due, 'Y/m/d') : null,
            'statusLabel' => Opportunity::statusOptions()[$record->status] ?? ($record->status ?: '—'),
            'stageLabel' => $record->stage?->name ?? '—',
            'productLabel' => $record->productInterestLabel(),
            'ownerLabel' => $record->owner?->name ?? '—',
            'followupLabel' => $record->followup?->name ?? ($record->owner?->name ?? '—'),
            'codeLabel' => 'OPP-' . str_pad((string) $record->id, 3, '0', STR_PAD_LEFT),
            'roleLabel' => $roleLabel,
            'listUrl' => OpportunityResource::getUrl(),
            'listLabel' => $listLabel,
            'pageTitle' => $this->getTitle(),
            'editUrl' => OpportunityResource::getUrl('edit', ['record' => $record]),
        ];
    }

    public function logActivityAction(): Action
    {
        return Action::make('logActivity')
            ->label('ثبت فعالیت')
            ->icon('heroicon-o-plus')
            ->color('primary')
            ->link()
            ->modalHeading('ثبت فعالیت')
            ->form([
                Select::make('type')->label('نوع')->options(Activity::typeOptions())->required()->native(false),
                DatePicker::make('due_at')->label('تاریخ')->default(now())->required()->native(false),
                Textarea::make('subject')->label('خلاصه')->required()->rows(3),
                Textarea::make('body')->label('نتیجه / واکنش (اختیاری)')->rows(2),
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
                Notification::make()->title('فعالیت ثبت شد')->success()->send();
            });
    }

    public function closeStepAction(): Action
    {
        return Action::make('closeStep')
            ->label('بستن گام')
            ->icon('heroicon-o-check-circle')
            ->color('success')
            ->modalHeading('بستن گام جاری')
            ->form([
                Textarea::make('result')->label('نتیجه این گام')->required()->rows(3),
                TextInput::make('next_step')->label('گام بعدی (اختیاری)'),
                DatePicker::make('next_step_due')->label('مهلت گام بعد')->native(false)->default(now()->addDays(3)),
                Select::make('stage_id')->label('مرحله قیف')->options(fn () => OpportunityStage::query()->where('is_active', true)->orderBy('sort_order')->pluck('name', 'id'))->default(fn () => $this->record->stage_id)->native(false),
                Select::make('status')->label('وضعیت')->options(Opportunity::statusOptions())->default(fn () => $this->record->status)->native(false),
            ])
            ->action(function (array $data): void {
                Activity::query()->create([
                    'company_id' => $this->record->company_id,
                    'opportunity_id' => $this->record->id,
                    'user_id' => auth()->id(),
                    'type' => Activity::TYPE_NOTE,
                    'subject' => 'بستن گام: ' . Str::limit((string) $this->record->next_step, 80),
                    'body' => trim((string) ($data['result'] ?? '')),
                    'due_at' => now(),
                    'is_done' => true,
                    'completed_at' => now(),
                ]);
                $next = trim((string) ($data['next_step'] ?? ''));
                $this->record->next_step = $next !== '' ? $next : null;
                $this->record->next_step_due = $next !== '' ? ($data['next_step_due'] ?? now()->addDays(3)) : null;
                if (! empty($data['stage_id'])) {
                    $this->record->stage_id = $data['stage_id'];
                }
                if (! empty($data['status'])) {
                    $this->record->status = $data['status'];
                }
                $this->record->save();
                if ($next !== '') {
                    Activity::query()->create([
                        'company_id' => $this->record->company_id,
                        'opportunity_id' => $this->record->id,
                        'user_id' => auth()->id(),
                        'type' => Activity::TYPE_TASK,
                        'subject' => $next,
                        'body' => 'گام جدید پس از بستن گام قبلی',
                        'due_at' => $this->record->next_step_due,
                        'is_done' => false,
                    ]);
                }
                Notification::make()->title('گام بسته شد')->success()->send();
            });
    }

    public function clearStepAction(): Action
    {
        return Action::make('clearStep')
            ->label('حذف گام')
            ->icon('heroicon-o-trash')
            ->color('gray')
            ->requiresConfirmation()
            ->action(function (): void {
                $this->record->next_step = null;
                $this->record->next_step_due = null;
                $this->record->save();
                Notification::make()->title('گام حذف شد')->success()->send();
            });
    }

    public function setStepAction(): Action
    {
        return Action::make('setStep')
            ->label('تعریف گام')
            ->icon('heroicon-o-flag')
            ->color('primary')
            ->form([
                Textarea::make('next_step')->label('گام بعدی')->required()->rows(2),
                DatePicker::make('next_step_due')->label('مهلت')->native(false)->default(now()->addDays(3))->required(),
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
                Notification::make()->title('گام تعریف شد')->success()->send();
            });
    }
}
