<?php

namespace App\Models\Scopes;

use App\Support\CurrentCompany;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Scope;

class CompanyScope implements Scope
{
    public function apply(Builder $builder, Model $model): void
    {
        if (app()->runningInConsole() && ! app()->runningUnitTests()) {
            return;
        }

        $companyId = CurrentCompany::id();

        if ($companyId) {
            $builder->where($model->getTable().'.company_id', $companyId);

            return;
        }

        // Fail closed on HTTP when no company selected
        if (! app()->runningInConsole()) {
            $builder->whereRaw('1 = 0');
        }
    }
}
