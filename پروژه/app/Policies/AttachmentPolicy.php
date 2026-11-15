<?php

namespace App\Policies;

use App\Models\Attachment;
use App\Models\User;
use Illuminate\Support\Facades\Gate;

class AttachmentPolicy
{
    public function view(User $user, Attachment $attachment): bool
    {
        if ((int) $attachment->company_id !== (int) $user->currentCompanyId()) {
            return false;
        }

        $attachable = $attachment->attachable;

        return $attachable !== null
            && Gate::forUser($user)->allows('view', $attachable);
    }

    public function delete(User $user, Attachment $attachment): bool
    {
        if ((int) $attachment->company_id !== (int) $user->currentCompanyId()) {
            return false;
        }

        $attachable = $attachment->attachable;

        return $attachable !== null
            && Gate::forUser($user)->allows('update', $attachable);
    }
}
