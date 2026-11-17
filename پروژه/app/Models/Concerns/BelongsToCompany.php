<?php

namespace App\Models\Concerns;

use App\Models\Company;
use App\Support\CompanyContext;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use RuntimeException;

trait BelongsToCompany
{
    public static function bootBelongsToCompany(): void
    {
        static::saving(function ($model): void {
            $companyId = app(CompanyContext::class)->requireCompanyId();

            if (! $model->exists) {
                if (! empty($model->company_id) && (int) $model->company_id !== $companyId) {
                    throw new RuntimeException('ثبت اطلاعات برای شرکتی غیر از شرکت جاری مجاز نیست.');
                }

                $model->company_id = $companyId;

                return;
            }

            if ((int) $model->company_id !== $companyId) {
                throw new RuntimeException('ویرایش اطلاعات خارج از شرکت جاری مجاز نیست.');
            }
        });

        static::addGlobalScope('company', function (Builder $builder): void {
            $companyId = app(CompanyContext::class)->currentCompanyId();

            if ($companyId === null) {
                $builder->whereRaw('1 = 0');

                return;
            }

            $builder->where(
                $builder->getModel()->getTable() . '.company_id',
                $companyId
            );
        });
    }

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }
}
