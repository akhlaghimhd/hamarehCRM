<?php

namespace App\Models;

use App\Models\Concerns\BelongsToCompany;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class CoverMaterial extends Model
{
    use BelongsToCompany, SoftDeletes;

    protected $fillable = [
        'company_id', 'product_line_id', 'code', 'name', 'is_active', 'sort',
    ];

    protected function casts(): array
    {
        return ['is_active' => 'boolean'];
    }

    public function productLine(): BelongsTo
    {
        return $this->belongsTo(ProductLine::class);
    }
}
