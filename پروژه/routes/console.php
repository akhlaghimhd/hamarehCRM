<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use App\Models\PlatformOwner;
use App\Models\User;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');


Artisan::command('platform-owner:grant {userId}', function (string $userId): int {
    $user = User::query()->findOrFail($userId);

    if (! $user->is_active) {
        $this->error('حساب غیرفعال نمی‌تواند مالک پلتفرم شود.');

        return self::FAILURE;
    }

    if (! $this->confirm("مالکیت پلتفرم به حساب {$user->name} ({$user->id}) اعطا شود؟")) {
        $this->comment('عملیات لغو شد.');

        return self::SUCCESS;
    }

    PlatformOwner::query()->firstOrCreate(
        ['user_id' => $user->id],
        ['granted_by_user_id' => null],
    );

    $this->info('مالک پلتفرم با موفقیت ثبت شد.');

    return self::SUCCESS;
})->purpose('اعطای مالکیت سراسری پلتفرم به یک حساب موجود؛ فقط از محیط امن CLI');
