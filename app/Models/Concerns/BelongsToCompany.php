<?php

namespace App\Models\Concerns;

use App\Models\Company;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

trait BelongsToCompany
{
    public static function bootBelongsToCompany(): void
    {
        static::creating(function ($model) {
            if (! empty($model->company_id)) {
                return;
            }

            $companyId = session('current_company_id')
                ?? Company::query()->value('id');

            if ($companyId) {
                $model->company_id = $companyId;
            }
        });

        static::addGlobalScope('company', function (Builder $builder) {
            $companyId = session('current_company_id');

            if ($companyId) {
                $builder->where(
                    $builder->getModel()->getTable() . '.company_id',
                    $companyId
                );
            }
        });
    }

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }
}
