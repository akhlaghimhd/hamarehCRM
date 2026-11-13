<?php

namespace App\Models;

use App\Models\Concerns\BelongsToCompany;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class ProductFamily extends Model
{
    use BelongsToCompany, SoftDeletes;

    protected $fillable = [
        'company_id',
        'code',
        'name',
        'description',
        'sort',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
            'sort' => 'integer',
        ];
    }

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    public function models(): HasMany
    {
        return $this->hasMany(ProductModel::class);
    }

    protected static function booted(): void
    {
        static::creating(function (self $model): void {
            if ($model->company_id) {
                return;
            }
            $user = auth()->user();
            if ($user && ! empty($user->company_id)) {
                $model->company_id = $user->company_id;
                return;
            }
            // fallback: اولین شرکت (تک‌شرکتی عملیاتی)
            $model->company_id = \App\Models\Company::query()->value('id');
        });
    }

}
