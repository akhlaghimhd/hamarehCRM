<?php

namespace App\Models;

use App\Models\Concerns\BelongsToCompany;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class StockMovement extends Model
{
    use BelongsToCompany;

    protected $fillable = [
        'company_id',
        'stock_level_id',
        'type',
        'qty',
        'qty_on_hand_after',
        'qty_reserved_after',
        'qty_in_production_after',
        'qty_ready_after',
        'reference_type',
        'reference_id',
        'reason',
        'actor_user_id',
        'occurred_at',
    ];

    protected function casts(): array
    {
        return [
            'qty' => 'integer',
            'qty_on_hand_after' => 'integer',
            'qty_reserved_after' => 'integer',
            'qty_in_production_after' => 'integer',
            'qty_ready_after' => 'integer',
            'occurred_at' => 'datetime',
        ];
    }

    public function stockLevel(): BelongsTo
    {
        return $this->belongsTo(StockLevel::class);
    }

    public function actor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'actor_user_id');
    }

    public function reference(): MorphTo
    {
        return $this->morphTo();
    }

    public function typeLabel(): string
    {
        return StockLevel::typeOptions()[$this->type] ?? $this->type;
    }
}
