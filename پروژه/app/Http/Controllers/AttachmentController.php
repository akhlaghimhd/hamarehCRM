<?php

namespace App\Http\Controllers;

use App\Actions\Attachments\DeleteAttachment;
use App\Models\Attachment;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\StreamedResponse;

class AttachmentController
{
    public function download(Attachment $attachment): StreamedResponse
    {
        Gate::authorize('view', $attachment);

        abort_unless(Storage::disk($attachment->disk)->exists($attachment->path), 404);

        return Storage::disk($attachment->disk)->download(
            $attachment->path,
            $attachment->original_name,
            ['Content-Type' => $attachment->mime_type],
        );
    }

    public function destroy(Attachment $attachment, DeleteAttachment $action): RedirectResponse
    {
        $action->handle($attachment);

        return back()->with('status', 'پیوست حذف شد.');
    }
}
