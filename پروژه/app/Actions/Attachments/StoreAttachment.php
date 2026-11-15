<?php

namespace App\Actions\Attachments;

use App\Models\Attachment;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Throwable;

class StoreAttachment
{
    private const MAX_SIZE_KB = 25600;

    public function handle(UploadedFile $file, object $attachable, User $actor, array $metadata = []): Attachment
    {
        if (! $file->isValid() || $file->getSize() < 1 || $file->getSize() > self::MAX_SIZE_KB * 1024) {
            throw ValidationException::withMessages([
                'file' => 'فایل معتبر نیست یا حجم آن از ۲۵ مگابایت بیشتر است.',
            ]);
        }

        $companyId = $actor->currentCompanyId();
        if (! $companyId || ! isset($attachable->company_id) || (int) $attachable->company_id !== $companyId) {
            throw ValidationException::withMessages([
                'attachment' => 'فایل فقط به موجودیت متعلق به شرکت جاری متصل می‌شود.',
            ]);
        }

        Gate::forUser($actor)->authorize('update', $attachable);

        $disk = (string) config('filesystems.attachments_disk', 'local');
        $extension = $file->guessExtension() ?: 'bin';
        $path = "companies/{$companyId}/attachments/".Str::uuid().".{$extension}";
        $storedPath = Storage::disk($disk)->putFileAs(dirname($path), $file, basename($path));

        if (! $storedPath) {
            throw ValidationException::withMessages([
                'file' => 'ذخیره فایل انجام نشد.',
            ]);
        }

        try {
            return DB::transaction(fn (): Attachment => Attachment::query()->create([
                'company_id' => $companyId,
                'attachable_type' => $attachable->getMorphClass(),
                'attachable_id' => $attachable->getKey(),
                'disk' => $disk,
                'path' => $storedPath,
                'original_name' => $file->getClientOriginalName(),
                'mime_type' => $file->getMimeType() ?: 'application/octet-stream',
                'size' => $file->getSize(),
                'checksum' => hash_file('sha256', $file->getRealPath()),
                'uploaded_by_user_id' => $actor->id,
                'metadata' => $metadata ?: null,
            ]));
        } catch (Throwable $exception) {
            Storage::disk($disk)->delete($storedPath);
            throw $exception;
        }
    }
}
