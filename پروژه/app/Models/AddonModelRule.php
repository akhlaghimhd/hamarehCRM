<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AddonModelRule extends Model
{
    protected $fillable = [
        'product_option_id', 'product_model_id', 'availability', 'is_preferred', 'note',
    ];

    protected function casts(): array
    {
        return ['is_preferred' => 'boolean'];
    }

    public function productOption(): BelongsTo
    {
        return $this->belongsTo(ProductOption::class);
    }

    public function productModel(): BelongsTo
    {
        return $this->belongsTo(ProductModel::class);
    }
}
