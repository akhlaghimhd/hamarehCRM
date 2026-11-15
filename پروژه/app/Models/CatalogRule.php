<?php

namespace App\Models;

use App\Models\Concerns\BelongsToCompany;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CatalogRule extends Model
{
    use BelongsToCompany;

    protected $fillable = [
        'company_id', 'product_line_id', 'name', 'scope',
        'condition_json', 'effect_json', 'level', 'message', 'is_active',
    ];

    protected function casts(): array
    {
        return [
            'condition_json' => 'array',
            'effect_json' => 'array',
            'is_active' => 'boolean',
        ];
    }

    public function productLine(): BelongsTo
    {
        return $this->belongsTo(ProductLine::class);
    }
}
