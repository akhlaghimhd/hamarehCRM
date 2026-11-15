<?php

namespace App\Actions\Tasks;

use App\Models\Task;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class SaveTask
{
    public function handle(array $data, User $actor, ?Task $task = null): Task
    {
        $companyId = $actor->currentCompanyId();
        if (! $companyId) {
            throw ValidationException::withMessages(['company_id' => 'شرکت جاری معتبر نیست.']);
        }

        $isNew = ! $task?->exists;
        if (! $isNew && (int) $task->company_id !== (int) $companyId) {
            throw ValidationException::withMessages(['task' => 'این کار متعلق به شرکت جاری نیست.']);
        }
        $idempotencyKey = $data['idempotency_key'] ?? null;
        if ($isNew && $idempotencyKey) {
            $existing = Task::query()->where('company_id', $companyId)->where('idempotency_key', $idempotencyKey)->first();
            if ($existing) {
                return $existing;
            }
        }
        if ($isNew && ! $actor->hasPermission('tasks.create')) {
            throw ValidationException::withMessages(['subject' => 'مجوز ایجاد کار را ندارید.']);
        }

        $assigneeId = (int) ($data['assignee_user_id'] ?? $actor->id);
        if (! $isNew && ! $actor->isCompanyAdmin()) {
            $canUpdateOwn = (int) $task->assignee_user_id === (int) $actor->id && $actor->hasPermission('tasks.update');
            $canUpdateTeam = $actor->hasPermission('team.tasks.manage')
                && $actor->managedUserIds()->contains((int) $task->assignee_user_id);
            if (! $canUpdateOwn && ! $canUpdateTeam) {
                throw ValidationException::withMessages(['subject' => 'مجوز ویرایش این کار را ندارید.']);
            }
        }

        $member = DB::table('company_user')->where('company_id', $companyId)->where('user_id', $assigneeId)->exists();
        if (! $member) {
            throw ValidationException::withMessages(['assignee_user_id' => 'مسئول کار باید عضو همین شرکت باشد.']);
        }

        if (! $actor->isCompanyAdmin() && $assigneeId !== (int) $actor->id) {
            $mayAssignTeam = $actor->hasPermission('team.tasks.assign') && $actor->managedUserIds()->contains($assigneeId);
            if (! $mayAssignTeam) {
                throw ValidationException::withMessages(['assignee_user_id' => 'برای ارجاع کار به این کاربر مجوز ندارید.']);
            }
        }

        return DB::transaction(function () use ($data, $actor, $task, $companyId, $assigneeId): Task {
            $task ??= new Task();
            $task->fill([
                'company_id' => $companyId,
                'subject' => $data['subject'],
                'description' => $data['description'] ?? null,
                'assignee_user_id' => $assigneeId,
                'reporter_user_id' => $task->exists ? $task->reporter_user_id : $actor->id,
                'priority' => $data['priority'] ?? Task::PRIORITY_NORMAL,
                'status' => $data['status'] ?? Task::STATUS_OPEN,
                'due_at' => $data['due_at'] ?? null,
                'completed_at' => ($data['status'] ?? null) === Task::STATUS_DONE ? ($task->completed_at ?? now()) : null,
                'idempotency_key' => $data['idempotency_key'] ?? $task->idempotency_key,
            ]);
            $task->save();
            return $task->refresh();
        });
    }
}
