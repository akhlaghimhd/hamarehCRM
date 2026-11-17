<?php

namespace App\Policies;

use App\Models\Activity;
use App\Models\User;

class ActivityPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->hasPermission('activities.view')
            || $user->hasPermission('team.activities.view');
    }

    public function view(User $user, Activity $activity): bool
    {
        return (int) $activity->company_id === $user->currentCompanyId()
            && $user->canViewActivityOwner((int) $activity->user_id);
    }

    public function create(User $user): bool
    {
        return $user->hasPermission('activities.manage');
    }

    public function update(User $user, Activity $activity): bool
    {
        return (int) $activity->company_id === $user->currentCompanyId()
            && $user->canManageActivityOwner((int) $activity->user_id);
    }

    public function delete(User $user, Activity $activity): bool
    {
        return $this->update($user, $activity);
    }
}
