<?php

namespace App\Models;

use App\Models\Concerns\BelongsToCompany;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class ProductModel extends Model
{
    use BelongsToCompany, SoftDeletes;

    protected $fillable = [
        'internal_note',
        'status',
        'catalog_season_id',
        'product_family_id',
        'company_id', 'product_line_id', 'code', 'name', 'description',
        'base_price', 'cost_price', 'attributes',
        'has_special_paper', 'includes_erasable_pen', 'includes_cloth',
        'is_active', 'sort',
    ];

    protected function casts(): array
    {
        return [
            'attributes' => 'array',
            'base_price' => 'decimal:0',
            'cost_price' => 'decimal:0',
            'has_special_paper' => 'boolean',
            'includes_erasable_pen' => 'boolean',
            'includes_cloth' => 'boolean',
            'is_active' => 'boolean',
        ];
    }

    public function productLine(): BelongsTo
    {
        return $this->belongsTo(ProductLine::class);
    }

    public function products(): HasMany
    {
        return $this->hasMany(Product::class);
    }

    public function addonRules(): HasMany
    {
        return $this->hasMany(AddonModelRule::class);
    }

    public function family()
    {
        return $this->belongsTo(ProductFamily::class, 'product_family_id');
    }

    public function season()
    {
        return $this->belongsTo(CatalogSeason::class, 'catalog_season_id');
    }

    public function sizes()
    {
        return $this->belongsToMany(ProductSize::class, 'product_model_size');
    }

    public function coverSkus()
    {
        return $this->belongsToMany(CoverSku::class, 'product_model_cover_sku');
    }

    public function options()
    {
        return $this->belongsToMany(ProductOption::class, 'product_model_option')
            ->withPivot(['is_recommended', 'min_qty', 'max_qty'])
            ;
    }

    public function isPublishable(): bool
    {
        if (! $this->product_family_id || ! $this->catalog_season_id) {
            return false;
        }
        $sizes = $this->sizes()->count();
        $skus = $this->coverSkus()->count();

        return $sizes >= 1 && $skus >= 1;
    }


    /**
     * کپی مدل برای کالکشن/کد جدید — pivots سایز، جلد، اقلام هم کپی می‌شوند.
     */
    public function duplicateForCatalog(?int $seasonId = null, ?string $code = null, ?string $name = null): self
    {
        $copy = $this->replicate();

        // شمارنده‌های withCount روی لیست — ستون DB نیستند
        foreach (array_keys($copy->getAttributes()) as $key) {
            if (str_ends_with($key, '_count')) {
                unset($copy->{$key});
            }
        }

        $copy->code = $code ?: ($this->code . '-COPY');
        $copy->name = $name ?: ($this->name . ' (کپی)');
        $copy->status = 'draft';
        $copy->is_active = true;
        $copy->includes_erasable_pen = false;
        $copy->includes_cloth = false;

        if ($seasonId) {
            $copy->catalog_season_id = $seasonId;
        }

        $base = $copy->code;
        $n = 1;
        while (static::withTrashed()
            ->where('company_id', $this->company_id)
            ->where('code', $copy->code)
            ->exists()) {
            $copy->code = $base . '-' . $n;
            $n++;
        }

        $copy->save();

        $copy->sizes()->sync($this->sizes()->allRelatedIds()->all());
        $copy->coverSkus()->sync($this->coverSkus()->allRelatedIds()->all());

        $optionSync = [];
        foreach ($this->options()->get() as $opt) {
            $optionSync[$opt->id] = [
                'is_recommended' => (bool) ($opt->pivot->is_recommended ?? false),
                'min_qty' => $opt->pivot->min_qty ?? null,
                'max_qty' => $opt->pivot->max_qty ?? null,
            ];
        }
        $copy->options()->sync($optionSync);

        return $copy->fresh();
    }

}
