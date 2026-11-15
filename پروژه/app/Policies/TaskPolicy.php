<?php

namespace App\Policies;

use App\Models\Task;
use App\Models\User;

class TaskPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->hasPermission('tasks.view') || $user->hasPermission('team.tasks.view');
    }

    public function view(User $user, Task $task): bool
    {
        return (int) $task->company_id === $user->currentCompanyId()
            && $user->canViewTaskOwner((int) $task->assignee_user_id);
    }

    public function create(User $user): bool
    {
        return $user->hasPermission('tasks.create');
    }

    public function update(User $user, Task $task): bool
    {
        return (int) $task->company_id === $user->currentCompanyId()
            && $user->canManageTaskOwner((int) $task->assignee_user_id);
    }

    public function delete(User $user, Task $task): bool
    {
        return $this->update($user, $task);
    }
}
