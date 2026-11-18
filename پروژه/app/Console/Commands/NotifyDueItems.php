<?php

namespace App\Console\Commands;

use App\Filament\Resources\OpportunityResource;
use App\Filament\Resources\TaskResource;
use App\Models\Activity;
use App\Models\Company;
use App\Models\Task;
use App\Models\User;
use App\Support\CompanyContext;
use Filament\Actions\Action;
use Filament\Notifications\Notification;
use Illuminate\Console\Command;

class NotifyDueItems extends Command
{
    protected $signature = 'crm:notify-due-items';

    protected $description = 'ارسال اعلان درون‌برنامه‌ای برای کارهای و فعالیت‌های سررسید امروز یا گذشته';

    public function handle(CompanyContext $companyContext): int
    {
        $sent = 0;

        foreach (Company::query()->where('is_active', true)->cursor() as $company) {
            $sent += (int) $companyContext->runForCompany($company->id, function () {
                return $this->notifyTasks() + $this->notifyActivities();
            });
        }

        $this->info('اعلان ارسال‌شده: '.$sent);

        return self::SUCCESS;
    }

    private function notifyTasks(): int
    {
        $count = 0;

        $tasks = Task::query()
            ->whereIn('status', [Task::STATUS_OPEN, Task::STATUS_IN_PROGRESS])
            ->whereNotNull('due_at')
            ->where('due_at', '<=', now()->endOfDay())
            ->whereNotNull('assignee_user_id')
            ->get();

        foreach ($tasks as $task) {
            $user = User::query()->find($task->assignee_user_id);
            if (! $user) {
                continue;
            }

            $key = 'task-due-'.$task->id.'-'.$task->due_at->format('Y-m-d');
            if ($this->alreadyNotified($user->id, $key)) {
                continue;
            }

            $isOverdue = $task->due_at->lt(now()->startOfDay());

            Notification::make()
                ->title($isOverdue ? 'کار سررسید گذشته' : 'کار سررسید امروز')
                ->body($task->subject)
                ->warning()
                ->actions([
                    Action::make('open')
                        ->label('مشاهده کار')
                        ->url(TaskResource::getUrl('edit', ['record' => $task], panel: 'admin'))
                        ->markAsRead(),
                ])
                ->sendToDatabase($user);

            $this->remember($user->id, $key);
            $count++;
        }

        return $count;
    }

    private function notifyActivities(): int
    {
        $count = 0;

        $activities = Activity::query()
            ->where('is_done', false)
            ->whereNotNull('due_at')
            ->where('due_at', '<=', now()->endOfDay())
            ->whereNotNull('user_id')
            ->get();

        foreach ($activities as $activity) {
            $user = User::query()->find($activity->user_id);
            if (! $user) {
                continue;
            }

            $key = 'activity-due-'.$activity->id.'-'.$activity->due_at->format('Y-m-d');
            if ($this->alreadyNotified($user->id, $key)) {
                continue;
            }

            $notification = Notification::make()
                ->title('فعالیت سررسیددار')
                ->body($activity->subject ?: 'فعالیت بدون موضوع')
                ->warning();

            if ($activity->opportunity_id) {
                $notification->actions([
                    Action::make('open')
                        ->label('مشاهده فرصت')
                        ->url(OpportunityResource::getUrl('view', ['record' => $activity->opportunity_id], panel: 'admin'))
                        ->markAsRead(),
                ]);
            }

            $notification->sendToDatabase($user);
            $this->remember($user->id, $key);
            $count++;
        }

        return $count;
    }

    private function alreadyNotified(int $userId, string $key): bool
    {
        return cache()->has($this->cacheKey($userId, $key));
    }

    private function remember(int $userId, string $key): void
    {
        cache()->put($this->cacheKey($userId, $key), true, now()->addDays(2));
    }

    private function cacheKey(int $userId, string $key): string
    {
        return 'crm-notify:'.$userId.':'.$key;
    }
}
