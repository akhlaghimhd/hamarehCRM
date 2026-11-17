<?php

namespace App\Actions\Identity;

use App\Models\Company;
use App\Models\User;
use App\Support\CompanyMembershipAccess;
use Filament\Notifications\Notification;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Support\Facades\DB;

class SetCompanyStatus
{
    public function handle(Company $record, bool $isActive, User $actor): bool
    {
        if (! app(CompanyMembershipAccess::class)->isAdmin($actor, (int) $record->id)) {
            throw new AuthorizationException('فقط مدیر همان شرکت می‌تواند وضعیت آن را تغییر دهد.');
        }

        return DB::transaction(function () use ($record, $isActive, $actor): bool {
            $company = Company::query()->whereKey($record->id)->lockForUpdate()->firstOrFail();
            $oldState = (bool) $company->is_active;

            if ($oldState === $isActive) {
                return $oldState;
            }

            DB::table('audit_events')->insert([
                'company_id' => $company->id,
                'auditable_type' => $company->getMorphClass(),
                'auditable_id' => $company->id,
                'actor_user_id' => $actor->id,
                'event' => $isActive ? 'activated' : 'deactivated',
                'old_values' => json_encode(['is_active' => $oldState], JSON_THROW_ON_ERROR | JSON_UNESCAPED_UNICODE),
                'new_values' => json_encode(['is_active' => $isActive], JSON_THROW_ON_ERROR | JSON_UNESCAPED_UNICODE),
                'metadata' => json_encode(['source' => 'company_table_toggle'], JSON_THROW_ON_ERROR),
                'occurred_at' => now(),
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            $company->forceFill(['is_active' => $isActive])->save();

            Notification::make()
                ->title($isActive ? 'شرکت فعال شد' : 'شرکت غیرفعال شد')
                ->success()
                ->send();

            if (! $isActive && (int) session('current_company_id') === (int) $company->id) {
                session()->forget('current_company_id');
            }

            return $isActive;
        });
    }
}
