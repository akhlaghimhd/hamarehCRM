<?php

namespace App\Support;

use App\Models\Company;
use App\Models\User;
use Closure;
use Illuminate\Support\Facades\Auth;
use RuntimeException;

class CompanyContext
{
    private ?int $systemCompanyId = null;

    public function currentCompanyId(?User $user = null): ?int
    {
        if ($this->systemCompanyId !== null) {
            return $this->systemCompanyId;
        }

        $user ??= Auth::user();

        if ($user instanceof User) {
            if (! (bool) $user->is_active) {
                return null;
            }

            $selectedCompanyId = session('current_company_id');

            if ($selectedCompanyId !== null) {
                return $this->isAvailableTo($user, (int) $selectedCompanyId)
                    ? (int) $selectedCompanyId
                    : null;
            }

            return $this->defaultCompanyId($user);
        }

        // Console work may opt into one company by setting an explicit context.
        if (app()->runningInConsole()) {
            $selectedCompanyId = session('current_company_id');

            if ($selectedCompanyId !== null && $this->isActiveCompany((int) $selectedCompanyId)) {
                return (int) $selectedCompanyId;
            }
        }

        return null;
    }

    public function requireCompanyId(?User $user = null): int
    {
        $companyId = $this->currentCompanyId($user);

        if ($companyId === null) {
            throw new RuntimeException('شرکت جاری معتبر نیست یا زمینه شرکت مشخص نشده است.');
        }

        return $companyId;
    }

    public function defaultCompanyId(User $user): ?int
    {
        if (! (bool) $user->is_active) {
            return null;
        }

        $companyId = $user->companies()
            ->wherePivot('is_default', true)
            ->where('companies.is_active', true)
            ->value('companies.id');

        return $companyId === null ? null : (int) $companyId;
    }

    public function isAvailableTo(User $user, int $companyId): bool
    {
        if (! (bool) $user->is_active) {
            return false;
        }

        return $user->companies()
            ->where('companies.id', $companyId)
            ->where('companies.is_active', true)
            ->exists();
    }

    public function selectForUser(User $user, int $companyId): void
    {
        if (! $this->isAvailableTo($user, $companyId)) {
            throw new RuntimeException('شرکت انتخاب‌شده فعال نیست یا کاربر عضو آن نیست.');
        }

        session(['current_company_id' => $companyId]);
    }

    public function clearSelection(): void
    {
        session()->forget('current_company_id');
    }

    /**
     * Run trusted system work with an explicit active company context.
     */
    public function runForCompany(int $companyId, Closure $callback): mixed
    {
        if (! app()->runningInConsole()) {
            throw new RuntimeException('زمینه سیستمی شرکت فقط در اجرای معتبر Console مجاز است.');
        }

        $company = Company::query()
            ->where('is_active', true)
            ->find($companyId);

        if (! $company) {
            throw new RuntimeException('شرکت عملیاتی معتبر و فعالی برای اجرای سیستم پیدا نشد.');
        }

        $previousCompanyId = $this->systemCompanyId;
        $this->systemCompanyId = (int) $company->id;

        try {
            return $callback($company);
        } finally {
            $this->systemCompanyId = $previousCompanyId;
        }
    }

    private function isActiveCompany(int $companyId): bool
    {
        return Company::query()
            ->whereKey($companyId)
            ->where('is_active', true)
            ->exists();
    }
}
