<?php

namespace App\\Http\\Controllers;

use App\\Actions\\Attachments\\DeleteAttachment;
use App\\Models\\Attachment;
use Illuminate\\Http\\RedirectResponse;
use Illuminate\\Support\\Facades\\Gate;
use Illuminate\\Support\\Facades\\Storage;
use Symfony\\Component\\HttpFoundation\\StreamedResponse;

class AttachmentController
{
    private const PREVIEWABLE_MIME_TYPES = [
        'image/jpeg',
        'image/png',
        'image/gif',
        'image/webp',
    ];

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

    public function preview(Attachment $attachment): StreamedResponse
    {
        Gate::authorize('view', $attachment);

        abort_unless(in_array($attachment->mime_type, self::PREVIEWABLE_MIME_TYPES, true), 404);
        abort_unless(Storage::disk($attachment->disk)->exists($attachment->path), 404);

        return Storage::disk($attachment->disk)->response(
            $attachment->path,
            $attachment->original_name,
            [
                'Content-Type' => $attachment->mime_type,
                'X-Content-Type-Options' => 'nosniff',
                'Cache-Control' => 'private, no-store',
            ],
            'inline',
        );
    }

    public function destroy(Attachment $attachment, DeleteAttachment $action): RedirectResponse
    {
        $action->handle($attachment);

        return back()->with('status', 'پیوست حذف شد.');
    }
}
