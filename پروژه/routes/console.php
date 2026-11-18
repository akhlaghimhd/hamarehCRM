<?php

use Illuminate\Support\Facades\Schedule;

use App\Models\PlatformOwner;
use App\Models\User;
use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

Artisan::command('platform-owner:grant {userId}', function (string $userId): int {
    $user = User::query()->find($userId);

    if (! $user) {
        $this->error('کاربر یافت نشد.');

        return self::FAILURE;
    }

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

Artisan::command('platform-owner:create', function (): int {
    $data = [
        'name' => trim((string) $this->ask('نام مالک پلتفرم')),
        'username' => trim((string) $this->ask('نام کاربری مالک پلتفرم')),
        'email' => trim((string) $this->ask('ایمیل مالک پلتفرم')),
        'password' => (string) $this->secret('گذرواژه مالک پلتفرم'),
        'password_confirmation' => (string) $this->secret('تکرار گذرواژه'),
    ];

    $validated = Validator::make($data, [
        'name' => ['required', 'string', 'max:255'],
        'username' => ['required', 'string', 'max:100', 'alpha_dash', 'unique:users,username'],
        'email' => ['required', 'email', 'max:255', 'unique:users,email'],
        'password' => ['required', 'string', 'min:6', 'same:password_confirmation'],
        'password_confirmation' => ['required', 'string'],
    ], [
        'username.unique' => 'این نام کاربری قبلاً ثبت شده است.',
        'email.unique' => 'این ایمیل قبلاً ثبت شده است.',
        'password.min' => 'گذرواژه باید حداقل ۶ نویسه باشد.',
        'password.same' => 'تکرار گذرواژه با گذرواژه یکسان نیست.',
    ])->validate();

    if (! $this->confirm('حساب مستقل مالک پلتفرم ایجاد شود؟')) {
        $this->comment('عملیات لغو شد.');

        return self::SUCCESS;
    }

    $owner = DB::transaction(function () use ($validated): User {
        $user = User::query()->create([
            'name' => $validated['name'],
            'username' => $validated['username'],
            'email' => $validated['email'],
            'password' => $validated['password'],
            'is_active' => true,
        ]);

        PlatformOwner::query()->create([
            'user_id' => $user->id,
            'granted_by_user_id' => null,
        ]);

        return $user;
    });

    $this->info("مالک پلتفرم ایجاد شد. شناسه کاربر: {$owner->id}");

    return self::SUCCESS;
})->purpose('ساخت حساب مستقل و اولیه مالک پلتفرم؛ فقط از محیط امن CLI');

Schedule::command('crm:notify-due-items')->hourly();
