<?php

namespace App\Filament\Pages;

use App\Filament\Resources\OpportunityResource;
use App\Filament\Resources\TaskResource;
use App\Models\Activity;
use App\Models\AuditEvent;
use App\Models\Task;
use App\Models\User;
use Filament\Pages\Page;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;
use UnitEnum;

class TeamWorkspace extends Page
{
    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-rectangle-stack';
    protected static ?string $navigationLabel = 'نمای کلی تیم';
    protected static ?string $title = 'نمای کلی فعالیت‌های تیم';
    protected static ?string $slug = 'team-workspace';
    protected static string|UnitEnum|null $navigationGroup = 'کار تیمی';
    protected static ?int $navigationSort = 10;

    protected string $view = 'filament.pages.team-workspace';

    public ?int $ownerFilter = null;
    public ?string $statusFilter = null;
    public ?string $kindFilter = null;

    public static function canAccess(): bool
    {
        $user = auth()->user();

        return $user && ($user->isCompanyAdmin()
            || $user->hasPermission('activities.view')
            || $user->hasPermission('team.activities.view')
            || $user->hasPermission('tasks.view')
            || $user->hasPermission('team.tasks.view'));
    }

    public static function shouldRegisterNavigation(): bool
    {
        return static::canAccess();
    }

    protected function getViewData(): array
    {
        /** @var User $user */
        $user = auth()->user();
        $companyId = $user->currentCompanyId();

        if (! $companyId) {
            return ['activities' => collect(), 'tasks' => collect(), 'auditEvents' => collect(), 'ownerOptions' => [], 'summary' => []];
        }

        $companyMemberIds = DB::table('company_user')->where('company_id', $companyId)->pluck('user_id')->map(fn ($id) => (int) $id);
        $isAdmin = $user->isCompanyAdmin();

        $activityOwnerIds = collect();
        if ($isAdmin) {
            $activityOwnerIds = $companyMemberIds;
        } else {
            if ($user->hasPermission('activities.view')) {
                $activityOwnerIds->push((int) $user->id);
            }
            if ($user->hasPermission('team.activities.view')) {
                $activityOwnerIds = $activityOwnerIds->merge($user->managedUserIds());
            }
        }

        $taskOwnerIds = collect();
        if ($isAdmin) {
            $taskOwnerIds = $companyMemberIds;
        } else {
            if ($user->hasPermission('tasks.view')) {
                $taskOwnerIds->push((int) $user->id);
            }
            if ($user->hasPermission('team.tasks.view')) {
                $taskOwnerIds = $taskOwnerIds->merge($user->managedUserIds());
            }
        }

        $activityOwnerIds = $activityOwnerIds->unique()->values();
        $taskOwnerIds = $taskOwnerIds->unique()->values();

        $activitiesQuery = Activity::query()->with(['user', 'opportunity'])
            ->whereIn('user_id', $activityOwnerIds);
        $tasksQuery = Task::query()->with(['assignee', 'reporter'])
            ->whereIn('assignee_user_id', $taskOwnerIds);

        if ($this->ownerFilter) {
            if ($activityOwnerIds->contains($this->ownerFilter)) {
                $activitiesQuery->where('user_id', $this->ownerFilter);
            } else {
                $activitiesQuery->whereRaw('1 = 0');
            }

            if ($taskOwnerIds->contains($this->ownerFilter)) {
                $tasksQuery->where('assignee_user_id', $this->ownerFilter);
            } else {
                $tasksQuery->whereRaw('1 = 0');
            }
        }

        if ($this->statusFilter === 'open') {
            $activitiesQuery->where('is_done', false);
            $tasksQuery->whereIn('status', [Task::STATUS_OPEN, Task::STATUS_IN_PROGRESS]);
        } elseif ($this->statusFilter === 'done') {
            $activitiesQuery->where('is_done', true);
            $tasksQuery->where('status', Task::STATUS_DONE);
        } elseif ($this->statusFilter === 'overdue') {
            $activitiesQuery->where('is_done', false)->whereNotNull('due_at')->where('due_at', '<', now());
            $tasksQuery->whereNotIn('status', [Task::STATUS_DONE, Task::STATUS_CANCELLED])->whereNotNull('due_at')->where('due_at', '<', now());
        }

        if ($this->kindFilter === 'activities') {
            $tasksQuery->whereRaw('1 = 0');
        } elseif ($this->kindFilter === 'tasks') {
            $activitiesQuery->whereRaw('1 = 0');
        }

        $activities = $activitiesQuery->latest('due_at')->limit(25)->get();
        $tasks = $tasksQuery->latest('due_at')->limit(25)->get();

        $activityIds = Activity::query()->whereIn('user_id', $activityOwnerIds)->pluck('id');
        $taskIds = Task::query()->whereIn('assignee_user_id', $taskOwnerIds)->pluck('id');

        $auditEvents = AuditEvent::query()
            ->with('actor')
            ->where('company_id', $companyId)
            ->where(function (Builder $query) use ($activityIds, $taskIds): void {
                $query->where(function (Builder $q) use ($activityIds): void {
                    $q->where('auditable_type', (new Activity())->getMorphClass())->whereIn('auditable_id', $activityIds);
                })->orWhere(function (Builder $q) use ($taskIds): void {
                    $q->where('auditable_type', (new Task())->getMorphClass())->whereIn('auditable_id', $taskIds);
                });
            })
            ->latest('occurred_at')
            ->limit(20)
            ->get();

        $ownerIds = $activityOwnerIds->merge($taskOwnerIds)->unique()->values();
        $ownerOptions = User::query()->whereIn('id', $ownerIds)->orderBy('name')->pluck('name', 'id')->all();

        return [
            'activities' => $activities,
            'tasks' => $tasks,
            'auditEvents' => $auditEvents,
            'ownerOptions' => $ownerOptions,
            'summary' => [
                'activities' => $activitiesQuery->count(),
                'tasks' => $tasksQuery->count(),
                'overdueActivities' => Activity::query()->whereIn('user_id', $activityOwnerIds)->where('is_done', false)->whereNotNull('due_at')->where('due_at', '<', now())->count(),
                'overdueTasks' => Task::query()->whereIn('assignee_user_id', $taskOwnerIds)->whereNotIn('status', [Task::STATUS_DONE, Task::STATUS_CANCELLED])->whereNotNull('due_at')->where('due_at', '<', now())->count(),
            ],
            'taskCreateUrl' => TaskResource::getUrl('create'),
            'taskListUrl' => TaskResource::getUrl('index'),
            'opportunityListUrl' => OpportunityResource::getUrl('index'),
            'canCreateTask' => $user->hasPermission('tasks.create'),
            'canViewOpportunities' => $user->hasPermission('opportunities.view'),
        ];
    }
}
