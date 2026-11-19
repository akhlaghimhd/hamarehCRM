<?php

namespace App\Models;

use App\Models\Concerns\BelongsToCompany;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class OpportunityStage extends Model
{
    use BelongsToCompany;

    protected $fillable = [
        'company_id',
        'code',
        'name',
        'description',
        'sort_order',
        'is_won',
        'is_lost',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'sort_order' => 'integer',
            'is_won' => 'boolean',
            'is_lost' => 'boolean',
            'is_active' => 'boolean',
        ];
    }

    public function opportunities(): HasMany
    {
        return $this->hasMany(Opportunity::class, 'stage_id');
    }

    public function isTerminal(): bool
    {
        return $this->is_won || $this->is_lost;
    }

    /**
     * اگر برای شرکت جاری مرحله‌ای نباشد، قیف پیش‌فرض را می‌سازد.
     */
    public static function ensureDefaults(): void
    {
        if (static::query()->where('is_active', true)->exists()) {
            return;
        }

        $stages = [
            ['code' => 'lead', 'name' => 'سرنخ اولیه', 'description' => 'اولین تماس یا معرفی؛ هنوز جلسه رسمی یا نیاز مشخص نشده.', 'sort_order' => 10, 'is_won' => false, 'is_lost' => false],
            ['code' => 'qualify', 'name' => 'واجد شرایط', 'description' => 'بودجه، زمان خرید و نقش تصمیم‌گیر تا حدی روشن است.', 'sort_order' => 20, 'is_won' => false, 'is_lost' => false],
            ['code' => 'meeting', 'name' => 'جلسه / نمایش', 'description' => 'جلسه حضوری یا آنلاین برگزار شده یا قطعی شده است.', 'sort_order' => 30, 'is_won' => false, 'is_lost' => false],
            ['code' => 'proposal', 'name' => 'پیشنهاد ارسال‌شده', 'description' => 'پیشنهاد قیمت یا طرح رسمی برای مشتری ارسال شده.', 'sort_order' => 40, 'is_won' => false, 'is_lost' => false],
            ['code' => 'negotiate', 'name' => 'مذاکره', 'description' => 'چانه‌زنی روی قیمت، تیراژ یا شرایط قرارداد.', 'sort_order' => 50, 'is_won' => false, 'is_lost' => false],
            ['code' => 'won', 'name' => 'بسته شدن موفق', 'description' => 'سفارش قطعی یا قرارداد امضا شده.', 'sort_order' => 60, 'is_won' => true, 'is_lost' => false],
            ['code' => 'lost', 'name' => 'بسته شدن ناموفق', 'description' => 'فرصت از دست رفته؛ دلیل باخت را ثبت کنید.', 'sort_order' => 70, 'is_won' => false, 'is_lost' => true],
        ];

        foreach ($stages as $stage) {
            static::query()->create(array_merge($stage, [
                'is_active' => true,
            ]));
        }
    }
}
