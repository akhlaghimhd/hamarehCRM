<?php

namespace App\Actions\Attachments;

use App\Models\Attachment;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Storage;
use RuntimeException;
use Throwable;

class DeleteAttachment
{
    public function handle(Attachment $attachment): void
    {
        Gate::authorize('delete', $attachment);

        $disk = Storage::disk($attachment->disk);
        $path = $attachment->path;

        $attachment->delete();

        try {
            if ($disk->exists($path) && ! $disk->delete($path)) {
                throw new RuntimeException('حذف فایل از فضای ذخیره‌سازی انجام نشد.');
            }
        } catch (Throwable $exception) {
            $attachment->restore();

            throw $exception;
        }
    }
}
