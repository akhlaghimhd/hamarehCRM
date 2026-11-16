<?php

namespace App\Actions\Identity;

use App\Models\AuditEvent;
use App\Models\User;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class UpdateUserAccount
{
    /**
     * Account status is global. A company manager may deactivate a user only
     * when that account has no membership in another company.
     *
     * @param array<string, mixed> $data
     */
    public function assertCanChangeStatus(User $target, array $data, User $actor, int $companyId): void
    {
        if (
            ! $actor->is_active
            || (int) $actor->currentCompanyId() !== $companyId
            || ! $actor->hasPermission('users.manage')
        ) {
            throw new AuthorizationException('اجازه تغییر وضعیت حساب در این شرکت را ندارید.');
        }

        if (! DB::table('company_user')
            ->where('company_id', $companyId)
            ->where('user_id', $target->id)
            ->exists()) {
            throw ValidationException::withMessages([
                'is_active' => 'کاربر عضو شرکت جاری نیست.',
            ]);
        }

        if (! array_key_exists('is_active', $data)) {
            return;
        }

        $requestedStatus = (bool) $data['is_active'];

        if ($requestedStatus === (bool) $target->is_active) {
            return;
        }

        if (! $requestedStatus && (int) $target->id === (int) $actor->id) {
            throw ValidationException::withMessages([
                'is_active' => 'غیرفعال‌سازی حساب خودتان از داخل شرکت مجاز نیست.',
            ]);
        }

        $hasOtherCompanyMembership = DB::table('company_user')
            ->where('user_id', $target->id)
            ->where('company_id', '<>', $companyId)
            ->exists();

        if ($hasOtherCompanyMembership) {
            throw ValidationException::withMessages([
                'is_active' => 'تغییر وضعیت حساب سراسری کاربر چندشرکتی از داخل یک شرکت مجاز نیست؛ عضویت شرکت جاری را جداگانه مدیریت کنید.',
            ]);
        }

        $hasDirectReports = DB::table('company_user')
            ->where('company_id', $companyId)
            ->where('manager_user_id', $target->id)
            ->exists();

        if ($hasDirectReports) {
            throw ValidationException::withMessages([
                'is_active' => 'ابتدا مدیر مستقیم اعضای زیرمجموعه این کاربر را تغییر دهید.',
            ]);
        }
    }

    public function recordStatusChange(User $target, bool $wasActive, User $actor, int $companyId): void
    {
        if ($wasActive === (bool) $target->is_active) {
            return;
        }

        if ((int) $actor->currentCompanyId() !== $companyId) {
            throw new AuthorizationException('ثبت تغییر وضعیت خارج از شرکت جاری مجاز نیست.');
        }

        AuditEvent::query()->create([
            'company_id' => $companyId,
            'auditable_type' => $target->getMorphClass(),
            'auditable_id' => $target->id,
            'actor_user_id' => $actor->id,
            'event' => 'account_status_changed',
            'old_values' => ['is_active' => $wasActive],
            'new_values' => ['is_active' => (bool) $target->is_active],
            'metadata' => ['source' => 'company_user_management'],
            'occurred_at' => now(),
        ]);
    }
}
