<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ProductOptionAttribute extends Model
{
    protected $fillable = [
        'product_option_id', 'key', 'label', 'value_type',
        'options_json', 'value', 'is_required', 'affects_inventory', 'sort',
    ];

    protected function casts(): array
    {
        return [
            'options_json' => 'array',
            'is_required' => 'boolean',
            'affects_inventory' => 'boolean',
        ];
    }

    public function productOption(): BelongsTo
    {
        return $this->belongsTo(ProductOption::class);
    }

    /** برای فرم: گزینه‌ها به‌صورت چندخطی */
    public function getOptionsTextAttribute(): string
    {
        $opts = $this->options_json ?? [];
        if (! is_array($opts)) {
            return '';
        }
        // پشتیبانی از ['a','b'] یا [['value'=>'a','label'=>'a']]
        return collect($opts)->map(function ($o) {
            if (is_array($o)) {
                return $o['label'] ?? $o['value'] ?? '';
            }

            return (string) $o;
        })->filter()->implode("\n");
    }

    public function setOptionsTextAttribute(?string $text): void
    {
        $lines = collect(preg_split('/\r\n|\r|\n/', (string) $text))
            ->map(fn ($l) => trim($l))
            ->filter()
            ->values()
            ->all();
        $this->attributes['options_json'] = json_encode($lines, JSON_UNESCAPED_UNICODE);
    }
}
