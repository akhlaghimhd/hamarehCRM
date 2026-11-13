<?php

namespace App\Models;

use App\Models\Concerns\BelongsToCompany;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class ProductLine extends Model
{
    use BelongsToCompany, SoftDeletes;

    protected $fillable = [
        'company_id', 'code', 'name', 'type', 'lifecycle_status', 'brand', 'unit',
        'description', 'short_description', 'base_price', 'cost_price',
        'attributes', 'is_active', 'sort',
    ];

    protected function casts(): array
    {
        return [
            'attributes' => 'array',
            'is_active' => 'boolean',
            'base_price' => 'decimal:0',
            'cost_price' => 'decimal:0',
        ];
    }

    public function productModels(): HasMany
    {
        return $this->hasMany(ProductModel::class);
    }
}
