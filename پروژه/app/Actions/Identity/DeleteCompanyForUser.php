<?php

namespace App\Actions\Identity;

use App\Models\AuditEvent;
use App\Models\Company;
use App\Models\User;
use App\Support\CompanyContext;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Support\Facades\DB;

class DeleteCompanyForUser
{
    public function handle(Company $record, User $actor): void
    {
        if (! $actor->is_active || ! $actor->hasPermission('companies.manage')) {
            throw new AuthorizationException('اجازه حذف شرکت را ندارید.');
        }

        $companyId = app(CompanyContext::class)->requireCompanyId($actor);

        if ($companyId !== (int) $record->getKey()) {
            throw new AuthorizationException('حذف شرکتی خارج از فضای کاری جاری مجاز نیست.');
        }

        DB::transaction(function () use ($record, $actor, $companyId): void {
            $company = Company::query()
                ->whereKey($record->getKey())
                ->lockForUpdate()
                ->firstOrFail();

            if (
                (int) $company->getKey() !== $companyId
                || ! $company->is_active
                || (int) $actor->currentCompanyId() !== $companyId
            ) {
                throw new AuthorizationException('شرکت جاری دیگر فعال یا در دسترس نیست.');
            }

            $deletedAt = now();

            AuditEvent::query()->create([
                'company_id' => $companyId,
                'auditable_type' => $company->getMorphClass(),
                'auditable_id' => $company->getKey(),
                'actor_user_id' => $actor->id,
                'event' => 'deleted',
                'old_values' => [
                    'name' => $company->name,
                    'slug' => $company->slug,
                    'is_active' => (bool) $company->is_active,
                ],
                'new_values' => [
                    'deleted_at' => $deletedAt->toISOString(),
                ],
                'metadata' => ['source' => 'company_profile'],
                'occurred_at' => $deletedAt,
            ]);

            $company->delete();
        });
    }
}
