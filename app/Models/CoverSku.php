<?php

namespace App\Models;

use App\Models\Concerns\BelongsToCompany;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class CoverSku extends Model
{
    use BelongsToCompany, SoftDeletes;

    protected $fillable = [
        'company_id', 'product_line_id', 'cover_type_id',
        'cover_material_id', 'cover_structure_id',
        'sku', 'series_type', 'hardness', 'name',
        'material', 'color', 'structure', 'attributes',
        'stock_qty', 'is_active',
    ];

    protected function casts(): array
    {
        return [
            'attributes' => 'array',
            'is_active' => 'boolean',
            'stock_qty' => 'integer',
        ];
    }

    public function productLine(): BelongsTo
    {
        return $this->belongsTo(ProductLine::class);
    }

    public function coverMaterial(): BelongsTo
    {
        return $this->belongsTo(CoverMaterial::class);
    }

    public function coverStructure(): BelongsTo
    {
        return $this->belongsTo(CoverStructure::class);
    }

    public function stocks(): HasMany
    {
        return $this->hasMany(CoverSkuStock::class);
    }
}
