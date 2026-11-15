<?php

namespace App\Actions\Attachments;

use App\Models\Attachment;
use Illuminate\Support\Facades\Gate;

class DeleteAttachment
{
    public function handle(Attachment $attachment): void
    {
        Gate::authorize('delete', $attachment);
        // Retain the physical object for retention and a separate permanent-purge process.
        $attachment->delete();
    }
}
