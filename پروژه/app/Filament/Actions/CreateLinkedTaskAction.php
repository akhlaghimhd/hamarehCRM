<?php

namespace App\Filament\Actions;

use App\Actions\Tasks\SaveTask;
use App\Models\Task;
use App\Models\User;
use Filament\Actions\Action;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\Page;
use Illuminate\Database\Eloquent\Model;

class CreateLinkedTaskAction
{
    /**
     * @param  class-string<Model>  $linkableType
     * @param  callable(Model): string|null  $subjectDefault
     */
    public static function make(
        Page $page,
        string $linkableType,
        string $modalHeading = 'ایجاد وظیفه وابسته',
        ?callable $subjectDefault = null,
        string $idempotencyPrefix = 'linked',
        string $actionName = 'createLinkedTask',
    ): Action {
        return Action::make($actionName)
            ->label('وظیفه وابسته')
            ->icon('heroicon-o-clipboard-document-check')
            ->color('gray')
            ->visible(fn () => auth()->user()?->hasPermission('tasks.create') ?? false)
            ->modalHeading($modalHeading)
            ->modalDescription('یک کار موازی برای همکار می‌سازد. مالکیت پیگیری فرصت عوض نمی‌شود (برای تحویل کامل مالکیت از «ارجاع» استفاده کنید).')
            ->form([
                TextInput::make('subject')
                    ->label('عنوان کار')
                    ->required()
                    ->maxLength(255)
                    ->default(function () use ($page, $subjectDefault) {
                        $record = $page->getRecord();
                        if ($subjectDefault) {
                            return $subjectDefault($record);
                        }

                        return 'پیگیری: '.($record->title ?? $record->number ?? ('#'.$record->getKey()));
                    }),
                Textarea::make('description')
                    ->label('شرح')
                    ->rows(3),
                Select::make('assignee_user_id')
                    ->label('مسئول کار')
                    ->helperText('این نفر فقط مسئول همین وظیفه است؛ مسئول پیگیری فرصت همان قبلی می‌ماند.')
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
            ->action(function (array $data) use ($page, $linkableType, $idempotencyPrefix): void {
                $record = $page->getRecord();

                try {
                    $task = app(SaveTask::class)->handle([
                        'subject' => $data['subject'],
                        'description' => $data['description'] ?? null,
                        'assignee_user_id' => $data['assignee_user_id'] ?? auth()->id(),
                        'priority' => $data['priority'] ?? Task::PRIORITY_NORMAL,
                        'status' => Task::STATUS_OPEN,
                        'due_at' => $data['due_at'] ?? null,
                        'linkable_type' => $linkableType,
                        'linkable_id' => $record->getKey(),
                        'idempotency_key' => $idempotencyPrefix.'-'.$record->getKey().'-'.md5(($data['subject'] ?? '').'|'.($data['due_at'] ?? '')),
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
                    ->title('وظیفه وابسته ایجاد شد')
                    ->body($task->subject)
                    ->success()
                    ->send();
            });
    }
}
