<?php

namespace App\Models\Concerns;

use App\Models\Company;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use RuntimeException;

trait BelongsToCompany
{
    public static function bootBelongsToCompany(): void
    {
        static::creating(function ($model) {
            if (! empty($model->company_id)) {
                return;
            }

            $companyId = session('current_company_id');

            if (! $companyId && auth()->check()) {
                $companyId = auth()->user()->currentCompanyId();
            }

            if (! $companyId) {
                throw new RuntimeException('company_id الزامی است؛ شرکت جاری در نشست تنظیم نشده.');
            }

            $model->company_id = $companyId;
        });

        static::addGlobalScope('company', function (Builder $builder) {
            if (app()->runningInConsole() && ! auth()->check() && ! session()->has('current_company_id')) {
                return;
            }

            $companyId = session('current_company_id');

            // اگر نشست خالی است، از شرکت پیش‌فرض کاربر لاگین‌شده استفاده کن و نشست را پر کن
            if (! $companyId && auth()->check()) {
                $user = auth()->user();
                if (method_exists($user, 'currentCompanyId')) {
                    $companyId = $user->currentCompanyId();
                }
                if ($companyId) {
                    session(['current_company_id' => (int) $companyId]);
                }
            }

            if ($companyId) {
                $builder->where(
                    $builder->getModel()->getTable() . '.company_id',
                    $companyId
                );

                return;
            }

            // بدون شرکت جاری هیچ ردیف عملیاتی دیده نشود (جلوگیری از نشت)
            $builder->whereRaw('1 = 0');
        });
    }

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }
}
