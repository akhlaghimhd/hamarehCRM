<?php

namespace App\Models;

use App\Models\Concerns\BelongsToCompany;
use Illuminate\Database\Eloquent\Model;

class CompanySetting extends Model
{
    use BelongsToCompany;

    protected $fillable = [
        'company_id',
        'key',
        'type',
        'value',
        'validation_rules',
        'is_sensitive',
    ];

    protected function casts(): array
    {
        return [
            'validation_rules' => 'array',
            'is_sensitive' => 'boolean',
        ];
    }
}
