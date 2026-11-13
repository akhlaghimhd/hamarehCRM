<?php

namespace App\Models;

use App\Models\Concerns\BelongsToCompany;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class ProductOption extends Model
{
    use BelongsToCompany, SoftDeletes;

    protected $fillable = [
        'company_id', 'product_line_id', 'code', 'name', 'type',
        'unit_price', 'stock_qty', 'model_scope',
        'attributes', 'is_active', 'sort',
    ];

    protected function casts(): array
    {
        return [
            'attributes' => 'array',
            'is_active' => 'boolean',
            'unit_price' => 'decimal:0',
            'stock_qty' => 'integer',
        ];
    }

    public function productLine(): BelongsTo
    {
        return $this->belongsTo(ProductLine::class);
    }

    public function customAttributes(): HasMany
    {
        return $this->hasMany(ProductOptionAttribute::class)->orderBy('sort');
    }

    public function variants(): HasMany
    {
        return $this->hasMany(ProductOptionVariant::class);
    }

    public function modelRules(): HasMany
    {
        return $this->hasMany(AddonModelRule::class);
    }

    /** مدل‌هایی که این قلم برایشان مجاز است */
    public function allowedModelIds(): array
    {
        if ($this->model_scope === 'all') {
            return ProductModel::query()
                ->when($this->product_line_id, fn ($q) => $q->where('product_line_id', $this->product_line_id))
                ->pluck('id')
                ->all();
        }

        return $this->modelRules()
            ->whereIn('availability', ['allowed', 'required'])
            ->pluck('product_model_id')
            ->all();
    }
}
