<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ProductOptionVariant extends Model
{
    protected $fillable = [
        'product_option_id', 'combination', 'label',
        'unit_price', 'stock_qty', 'sku', 'is_active',
    ];

    protected function casts(): array
    {
        return [
            'combination' => 'array',
            'unit_price' => 'decimal:0',
            'stock_qty' => 'integer',
            'is_active' => 'boolean',
        ];
    }

    public function productOption(): BelongsTo
    {
        return $this->belongsTo(ProductOption::class);
    }

    public static function makeLabel(array $combination): string
    {
        return collect($combination)
            ->map(fn ($v, $k) => $v)
            ->filter()
            ->implode(' / ');
    }
}
