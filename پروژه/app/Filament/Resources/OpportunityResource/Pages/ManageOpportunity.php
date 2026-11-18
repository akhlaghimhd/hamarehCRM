<?php

namespace App\Filament\Resources\OpportunityResource\Pages;

use App\Filament\Resources\OpportunityResource;
use App\Filament\Resources\QuoteResource;
use App\Models\Activity;
use App\Models\Opportunity;
use App\Models\OpportunityStage;
use App\Models\OpportunityStep;
use App\Models\Quote;
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
            Action::make('markWon')
                ->label('برنده')
                ->icon('heroicon-o-trophy')
                ->color('success')
                ->requiresConfirmation()
                ->modalHeading('ثبت فرصت به‌عنوان برنده؟')
                ->modalDescription('مرحله و وضعیت به برنده تغییر می‌کند. پس از آن می‌توانید پیش‌فاکتور بسازید.')
                ->visible(fn () => OpportunityResource::canEdit($this->record)
                    && $this->record->status !== Opportunity::STATUS_WON
                    && $this->record->status !== Opportunity::STATUS_LOST)
                ->action(function (): void {
                    $this->record->markWon();
                    $this->record->refresh();

                    Notification::make()
                        ->title('فرصت برنده ثبت شد')
                        ->success()
                        ->send();
                }),

            Action::make('markLost')
                ->label('بازنده')
                ->icon('heroicon-o-x-circle')
                ->color('danger')
                ->visible(fn () => OpportunityResource::canEdit($this->record)
                    && $this->record->status !== Opportunity::STATUS_WON
                    && $this->record->status !== Opportunity::STATUS_LOST)
                ->modalHeading('ثبت فرصت به‌عنوان بازنده')
                ->form([
                    Select::make('lost_reason_code')
                        ->label('دلیل باخت')
                        ->options(Opportunity::lostReasonOptions())
                        ->required()
                        ->native(false),
                    Textarea::make('lost_reason')
                        ->label('توضیح (اختیاری)')
                        ->rows(3)
                        ->maxLength(500),
                ])
                ->action(function (array $data): void {
                    $code = $data['lost_reason_code'] ?? null;
                    $labels = Opportunity::lostReasonOptions();
                    $label = $labels[$code] ?? null;
                    $detail = trim((string) ($data['lost_reason'] ?? ''));
                    $reason = $detail !== ''
                        ? ($label ? $label.' — '.$detail : $detail)
                        : $label;

                    $this->record->markLost($reason, $code);
                    $this->record->refresh();

                    Notification::make()
                        ->title('فرصت بازنده ثبت شد')
                        ->success()
                        ->send();
                }),

            Action::make('createQuote')
                ->label('پیش‌فاکتور')
                ->icon('heroicon-o-document-text')
                ->color('primary')
                ->visible(function (): bool {
                    if (! OpportunityResource::canEdit($this->record)) {
                        return false;
                    }

                    if (! class_exists(QuoteResource::class)) {
                        return false;
                    }

                    return QuoteResource::canCreate();
                })
                ->requiresConfirmation()
                ->modalHeading('ایجاد پیش‌فاکتور از این فرصت؟')
                ->modalDescription('یک پیش‌فاکتور پیش‌نویس با اتصال به همین فرصت ساخته می‌شود.')
                ->action(function () {
                    try {
                        $quote = Quote::fromOpportunity($this->record);
                    } catch (\Throwable $e) {
                        Notification::make()
                            ->title('ساخت پیش‌فاکتور ممکن نیست')
                            ->body($e->getMessage())
                            ->danger()
                            ->send();

                        return null;
                    }

                    if ($this->record->status !== Opportunity::STATUS_WON) {
                        $this->record->markWon();
                        $this->record->refresh();
                    }

                    Notification::make()
                        ->title('پیش‌فاکتور ساخته شد')
                        ->body($quote->number)
                        ->success()
                        ->send();

                    return redirect(QuoteResource::getUrl('edit', ['record' => $quote]));
                }),

            Action::make('changeStage')
                ->label('تغییر مرحله')
                ->icon('heroicon-o-arrows-right-left')
                ->color('gray')
                ->visible(fn () => OpportunityResource::canEdit($this->record)
                    && $this->record->status !== Opportunity::STATUS_WON
                    && $this->record->status !== Opportunity::STATUS_LOST)
                ->modalHeading('تغییر مرحله قیف')
                ->form([
                    Select::make('stage_id')
                        ->label('مرحله')
                        ->options(fn () => OpportunityStage::query()
                            ->where('is_active', true)
                            ->orderBy('sort_order')
                            ->pluck('name', 'id'))
                        ->default(fn () => $this->record->stage_id)
                        ->required()
                        ->native(false),
                    Select::make('status')
                        ->label('وضعیت')
                        ->options(Opportunity::statusOptions())
                        ->default(fn () => $this->record->status)
                        ->required()
                        ->native(false),
                    Textarea::make('note')
                        ->label('یادداشت (اختیاری)')
                        ->rows(2)
                        ->maxLength(500),
                ])
                ->action(function (array $data): void {
                    $this->record->stage_id = $data['stage_id'] ?? $this->record->stage_id;
                    $this->record->status = $data['status'] ?? $this->record->status;
                    $this->record->save();

                    $note = trim((string) ($data['note'] ?? ''));
                    $stageName = OpportunityStage::query()->find($this->record->stage_id)?->name ?? '—';
                    Activity::query()->create([
                        'company_id' => $this->record->company_id,
                        'opportunity_id' => $this->record->id,
                        'opportunity_step_id' => $this->currentOpenStep()?->id,
                        'user_id' => auth()->id(),
                        'type' => Activity::TYPE_NOTE,
                        'subject' => 'تغییر مرحله به «'.$stageName.'»',
                        'body' => $note !== '' ? $note : null,
                        'due_at' => now(),
                        'is_done' => true,
                        'completed_at' => now(),
                    ]);

                    $this->record->refresh();

                    Notification::make()
                        ->title('مرحله به‌روز شد')
                        ->body($stageName)
                        ->success()
                        ->send();
                }),

            Action::make('refer')
                ->label('ارجاع')
                ->icon('heroicon-o-arrow-path-rounded-square')
                ->color('gray')
                ->visible(fn () => OpportunityResource::canEdit($this->record) && $this->currentOpenStep() !== null)
                ->modalHeading('ارجاع گام جاری')
                ->modalDescription('گام فعلی با فعالیت‌هایش بسته می‌شود و برای نفر مقصد گام جدید باز می‌شود.')
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

                    $open = $this->currentOpenStep();
                    if ($open) {
                        $this->closeOpenStep($open, trim((string) $data['close_result']), from: $from?->id);

                        Activity::query()->create([
                            'company_id' => $this->record->company_id,
                            'opportunity_id' => $this->record->id,
                            'opportunity_step_id' => $open->id,
                            'user_id' => $from?->id,
                            'type' => Activity::TYPE_NOTE,
                            'subject' => 'ارجاع: از ' . ($from?->name ?? '—') . ' به ' . $to->name,
                            'body' => 'گام برای «' . $to->name . '» باز شد.',
                            'due_at' => now(),
                            'is_done' => true,
                            'completed_at' => now(),
                        ]);
                    }

                    $this->openNewStep(
                        title: (string) $data['new_step'],
                        dueAt: $data['new_step_due'],
                        assignedUserId: $to->id,
                    );

                    $this->record->followup_user_id = $to->id;
                    $this->record->save();

                    Notification::make()
                        ->title('ارجاع انجام شد')
                        ->body('گام برای «' . $to->name . '» باز شد.')
                        ->success()
                        ->send();
                }),

            Action::make('delete')
                ->label('حذف فرصت')
                ->icon('heroicon-o-trash')
                ->color('danger')
                ->requiresConfirmation()
                ->modalHeading('حذف این فرصت؟')
                ->modalDescription('این عمل قابل بازگشت نیست.')
                ->visible(fn () => OpportunityResource::canEdit($this->record))
                ->action(function (): void {
                    $this->record->delete();
                    Notification::make()->title('فرصت حذف شد')->success()->send();
                    $this->redirect(OpportunityResource::getUrl());
                }),
        ];
    }

    protected function getViewData(): array
    {
        /** @var Opportunity $record */
        $record = $this->record->loadMissing([
            'stage',
            'owner',
            'followupUser',
            'customer',
            'steps.activities.user',
            'steps.assignedUser',
        ]);

        $this->ensureStepSyncedFromLegacy($record);

        $openStep = $record->steps->firstWhere('is_open', true);
        $closedSteps = $record->steps->where('is_open', false)->sortByDesc('closed_at')->values();

        $due = $openStep?->due_at ?? $record->next_step_due;
        $isOverdue = $openStep
            && $due
            && Carbon::parse($due)->startOfDay()->lt(now()->startOfDay());

        $roleKey = $record->decision_role ?: $record->contact_role;
        $roleMap = Opportunity::decisionRoleOptions();
        $roleLabel = $roleMap[$roleKey ?? ''] ?? null;

        $listLabel = OpportunityResource::getNavigationLabel()
            ?: OpportunityResource::getBreadcrumb()
            ?: 'فرصت‌ها';

        $openActivities = $openStep
            ? $openStep->activities->sortByDesc('id')->values()
            : collect();

        $assigneeLabel = $openStep?->assignedUser?->name
            ?? $record->followupUser?->name
            ?? $record->owner?->name
            ?? '—';

        return [
            'opportunity' => $record,
            'openStep' => $openStep,
            'closedSteps' => $closedSteps,
            'stepActivities' => $openActivities,
            'isOverdue' => $isOverdue,
            'dueLabel' => $due ? Fa::dateTime($due, 'Y/m/d') : null,
            'statusLabel' => Opportunity::statusOptions()[$record->status] ?? ($record->status ?: '—'),
            'stageLabel' => $record->stage?->name ?? '—',
            'productLabel' => $record->productInterestLabel(),
            'ownerLabel' => $record->owner?->name ?? '—',
            'followupLabel' => $record->followupUser?->name ?? ($record->owner?->name ?? '—'),
            'assigneeLabel' => $assigneeLabel,
            'codeLabel' => 'OPP-' . str_pad((string) $record->id, 3, '0', STR_PAD_LEFT),
            'roleLabel' => $roleLabel,
            'listUrl' => OpportunityResource::getUrl(),
            'listLabel' => $listLabel,
            'pageTitle' => $this->getTitle(),
            'editUrl' => OpportunityResource::getUrl('edit', ['record' => $record]),
        ];
    }

    protected function ensureStepSyncedFromLegacy(Opportunity $record): void
    {
        if ($record->steps->firstWhere('is_open', true)) {
            return;
        }

        if (! filled($record->next_step)) {
            return;
        }

        OpportunityStep::query()->create([
            'company_id' => $record->company_id,
            'opportunity_id' => $record->id,
            'assigned_user_id' => $record->followup_user_id ?: $record->owner_user_id,
            'title' => $record->next_step,
            'due_at' => $record->next_step_due,
            'is_open' => true,
            'sort_order' => ($record->steps->max('sort_order') ?? 0) + 1,
        ]);

        $record->setRelation(
            'steps',
            $record->steps()->with(['activities.user', 'assignedUser'])->orderByDesc('id')->get()
        );
    }

    protected function currentOpenStep(): ?OpportunityStep
    {
        return OpportunityStep::query()
            ->where('opportunity_id', $this->record->id)
            ->where('is_open', true)
            ->orderByDesc('id')
            ->first();
    }

    protected function closeOpenStep(OpportunityStep $step, string $result, ?int $from = null): void
    {
        $step->close($result);

        Activity::query()->create([
            'company_id' => $this->record->company_id,
            'opportunity_id' => $this->record->id,
            'opportunity_step_id' => $step->id,
            'user_id' => $from ?? auth()->id(),
            'type' => Activity::TYPE_NOTE,
            'subject' => 'نتیجه گام',
            'body' => $result,
            'due_at' => now(),
            'is_done' => true,
            'completed_at' => now(),
        ]);
    }

    protected function openNewStep(string $title, mixed $dueAt, ?int $assignedUserId = null): OpportunityStep
    {
        $step = OpportunityStep::query()->create([
            'company_id' => $this->record->company_id,
            'opportunity_id' => $this->record->id,
            'assigned_user_id' => $assignedUserId ?? auth()->id(),
            'title' => $title,
            'due_at' => $dueAt,
            'is_open' => true,
            'sort_order' => (int) OpportunityStep::query()->where('opportunity_id', $this->record->id)->max('sort_order') + 1,
        ]);

        $this->record->next_step = $title;
        $this->record->next_step_due = $dueAt;
        $this->record->save();

        return $step;
    }

    public function logActivityAction(): Action
    {
        return Action::make('logActivity')
            ->label('ثبت فعالیت')
            ->icon('heroicon-o-plus')
            ->color('primary')
            ->link()
            ->visible(fn () => $this->currentOpenStep() !== null)
            ->modalHeading('ثبت فعالیت')
            ->form([
                Select::make('type')->label('نوع')->options(Activity::typeOptions())->required()->native(false),
                DatePicker::make('due_at')->label('تاریخ')->default(now())->required()->native(false),
                Textarea::make('subject')->label('خلاصه')->required()->rows(3),
                Textarea::make('body')->label('نتیجه / واکنش (اختیاری)')->rows(2),
            ])
            ->action(function (array $data): void {
                $open = $this->currentOpenStep();
                if (! $open) {
                    Notification::make()->title('ابتدا یک گام باز تعریف کنید')->warning()->send();

                    return;
                }

                Activity::query()->create([
                    'company_id' => $this->record->company_id,
                    'opportunity_id' => $this->record->id,
                    'opportunity_step_id' => $open->id,
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
                Select::make('stage_id')
                    ->label('مرحله قیف')
                    ->options(fn () => OpportunityStage::query()->where('is_active', true)->orderBy('sort_order')->pluck('name', 'id'))
                    ->default(fn () => $this->record->stage_id)
                    ->native(false),
                Select::make('status')
                    ->label('وضعیت')
                    ->options(Opportunity::statusOptions())
                    ->default(fn () => $this->record->status)
                    ->native(false),
            ])
            ->action(function (array $data): void {
                $open = $this->currentOpenStep();
                if (! $open) {
                    Notification::make()->title('گام بازی وجود ندارد')->warning()->send();

                    return;
                }

                $this->closeOpenStep($open, trim((string) ($data['result'] ?? '')));

                if (! empty($data['stage_id'])) {
                    $this->record->stage_id = $data['stage_id'];
                }
                if (! empty($data['status'])) {
                    $this->record->status = $data['status'];
                }

                $next = trim((string) ($data['next_step'] ?? ''));
                if ($next !== '') {
                    $this->openNewStep($next, $data['next_step_due'] ?? now()->addDays(3));
                } else {
                    $this->record->next_step = null;
                    $this->record->next_step_due = null;
                    $this->record->save();
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
            ->size('sm')
            ->requiresConfirmation()
            ->modalHeading('حذف گام جاری؟')
            ->modalDescription('گام باز حذف می‌شود؛ فعالیت‌های ثبت‌شده زیر آن می‌مانند ولی گام بسته محسوب نمی‌شود.')
            ->action(function (): void {
                $open = $this->currentOpenStep();
                if ($open) {
                    $open->delete();
                }
                $this->record->next_step = null;
                $this->record->next_step_due = null;
                $this->record->save();
                Notification::make()->title('گام حذف شد')->success()->send();
            });
    }

    public function setStepAction(): Action
    {
        return Action::make('setStep')
            ->label('تعریف گام بعدی')
            ->icon('heroicon-o-flag')
            ->color('primary')
            ->form([
                Textarea::make('next_step')->label('گام بعدی')->required()->rows(2),
                DatePicker::make('next_step_due')->label('مهلت')->native(false)->default(now()->addDays(3))->required(),
            ])
            ->action(function (array $data): void {
                if ($this->currentOpenStep()) {
                    Notification::make()->title('ابتدا گام جاری را ببندید')->warning()->send();

                    return;
                }

                $this->openNewStep((string) $data['next_step'], $data['next_step_due']);
                Notification::make()->title('گام تعریف شد')->success()->send();
            });
    }
}
