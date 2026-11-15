<?php

namespace App\Models;

use App\Models\Concerns\BelongsToCompany;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class QuoteLine extends Model
{
    use BelongsToCompany;

    protected $fillable = [
        'company_id',
        'quote_id',
        'sort_order',
        'product_type',
        'product_model_id',
        'title',
        'description',
        'config_snapshot',
        'qty',
        'unit_price',
        'line_total',
    ];

    protected function casts(): array
    {
        return [
            'config_snapshot' => 'array',
            'qty' => 'decimal:2',
            'unit_price' => 'decimal:0',
            'line_total' => 'decimal:0',
        ];
    }

    public function quote(): BelongsTo
    {
        return $this->belongsTo(Quote::class);
    }

    protected static function booted(): void
    {
        static::saving(function (QuoteLine $line) {
            $line->line_total = round((float) $line->qty * (float) $line->unit_price, 0);
        });

        static::saved(function (QuoteLine $line) {
            $line->quote?->recalculateTotals();
        });

        static::deleted(function (QuoteLine $line) {
            $line->quote?->recalculateTotals();
        });
    }
}
