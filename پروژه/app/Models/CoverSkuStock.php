<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CoverSkuStock extends Model
{
    protected $fillable = [
        'cover_sku_id', 'color_primary_id', 'color_secondary_id', 'stock_qty', 'note',
    ];

    protected function casts(): array
    {
        return ['stock_qty' => 'integer'];
    }

    public function coverSku(): BelongsTo
    {
        return $this->belongsTo(CoverSku::class);
    }

    public function colorPrimary(): BelongsTo
    {
        return $this->belongsTo(CoverColor::class, 'color_primary_id');
    }

    public function colorSecondary(): BelongsTo
    {
        return $this->belongsTo(CoverColor::class, 'color_secondary_id');
    }
}
