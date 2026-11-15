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
            if (auth()->check()) {
                $user = auth()->user();
                $companyId = $user->currentCompanyId();

                if (! $companyId) {
                    throw new RuntimeException('شرکت جاری معتبر نیست یا کاربر عضو آن نیست.');
                }

                if (! empty($model->company_id) && (int) $model->company_id !== $companyId) {
                    throw new RuntimeException('کاربر مجاز به ثبت اطلاعات برای این شرکت نیست.');
                }

                $model->company_id = $companyId;

                return;
            }

            if (empty($model->company_id)) {
                throw new RuntimeException('company_id برای ثبت اطلاعات الزامی است.');
            }
        });

        static::addGlobalScope('company', function (Builder $builder) {
            if (app()->runningInConsole() && ! auth()->check() && ! session()->has('current_company_id')) {
                return;
            }

            $companyId = auth()->check()
                ? auth()->user()->currentCompanyId()
                : null;

            if ($companyId) {
                $builder->where(
                    $builder->getModel()->getTable() . '.company_id',
                    $companyId
                );

                return;
            }

            $builder->whereRaw('1 = 0');
        });
    }

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }
}
