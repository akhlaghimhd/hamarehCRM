<?php

namespace App\Filament\Resources\OpportunityResource\Actions;

use App\Actions\Tasks\SaveTask;
use App\Models\Opportunity;
use App\Models\Task;
use App\Models\User;
use Filament\Actions\Action;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\ViewRecord;

class CreateLinkedTaskAction
{
    public static function make(ViewRecord $page): Action
    {
        return Action::make('createTask')
            ->label('کار تیمی')
            ->icon('heroicon-o-clipboard-document-check')
            ->color('gray')
            ->visible(fn () => auth()->user()?->hasPermission('tasks.create') ?? false)
            ->modalHeading('ایجاد کار وابسته به این فرصت')
            ->form([
                TextInput::make('subject')
                    ->label('عنوان کار')
                    ->required()
                    ->maxLength(255)
                    ->default(fn () => 'پیگیری: '.($page->getRecord()->title ?: $page->getRecord()->organization_name)),
                Textarea::make('description')
                    ->label('شرح')
                    ->rows(3),
                Select::make('assignee_user_id')
                    ->label('مسئول')
                    ->options(fn () => User::query()->where('is_active', true)->pluck('name', 'id'))
                    ->default(fn () => auth()->id())
                    ->searchable()
                    ->required()
                    ->native(false),
                Select::make('priority')
                    ->label('اولویت')
                    ->options(Task::priorityOptions())
                    ->default(Task::PRIORITY_NORMAL)
                    ->required()
                    ->native(false),
                DatePicker::make('due_at')
                    ->label('سررسید')
                    ->native(false)
                    ->default(now()->addDays(2)),
            ])
            ->action(function (array $data) use ($page): void {
                /** @var Opportunity $record */
                $record = $page->getRecord();

                try {
                    $task = app(SaveTask::class)->handle([
                        'subject' => $data['subject'],
                        'description' => $data['description'] ?? null,
                        'assignee_user_id' => $data['assignee_user_id'] ?? auth()->id(),
                        'priority' => $data['priority'] ?? Task::PRIORITY_NORMAL,
                        'status' => Task::STATUS_OPEN,
                        'due_at' => $data['due_at'] ?? null,
                        'linkable_type' => Opportunity::class,
                        'linkable_id' => $record->id,
                        'idempotency_key' => 'opp-'.$record->id.'-'.md5(($data['subject'] ?? '').'|'.($data['due_at'] ?? '')),
                    ], auth()->user());
                } catch (\Throwable $e) {
                    Notification::make()
                        ->title('ایجاد کار ممکن نیست')
                        ->body($e->getMessage())
                        ->danger()
                        ->send();

                    return;
                }

                Notification::make()
                    ->title('کار ایجاد شد')
                    ->body($task->subject)
                    ->success()
                    ->send();
            });
    }
}
