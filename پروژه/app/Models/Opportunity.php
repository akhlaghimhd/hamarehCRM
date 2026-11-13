<?php

namespace App\Models;

use App\Models\Concerns\BelongsToCompany;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Opportunity extends Model
{
    use BelongsToCompany;
    use SoftDeletes;

    public const STATUS_ACTIVE = 'active';
    public const STATUS_NURTURING = 'nurturing';
    public const STATUS_PAUSED = 'paused';
    public const STATUS_WON = 'won';
    public const STATUS_LOST = 'lost';

    protected $fillable = [
        'company_id',
        'customer_id',
        'stage_id',
        'owner_user_id',
        'followup_user_id',
        'title',
        'organization_name',
        'contact_name',
        'contact_role',
        'mobile',
        'phone',
        'email',
        'address',
        'industry',
        'industry_other',
        'product_interest',
        'purchase_use',
        'qty_approx',
        'budget_band',
        'buy_timing',
        'value_focus',
        'value_other',
        'main_objection',
        'status',
        'amount',
        'probability',
        'source',
        'notes',
        'lost_reason',
        'lost_reason_code',
        'final_result',
        'next_step',
        'next_step_due',
        'won_at',
        'lost_at',
    ];

    protected function casts(): array
    {
        return [
            'amount' => 'decimal:0',
            'probability' => 'integer',
            'next_step_due' => 'date',
            'won_at' => 'datetime',
            'lost_at' => 'datetime',
            'product_interest' => 'array',
        ];
    }

    public static function statusOptions(): array
    {
        return [
            self::STATUS_ACTIVE => 'فعال',
            self::STATUS_NURTURING => 'در حال پرورش',
            self::STATUS_PAUSED => 'متوقف موقت',
            self::STATUS_WON => 'بسته‌شده — موفق',
            self::STATUS_LOST => 'بسته‌شده — ناموفق',
        ];
    }

    public static function industryOptions(): array
    {
        return [
            'education' => 'آموزش / مدرسه',
            'corporate' => 'سازمانی / شرکتی',
            'branding' => 'برندینگ / تبلیغات',
            'exhibition' => 'نمایشگاه',
            'government' => 'دولتی',
            'other' => 'سایر',
        ];
    }

    public static function valueFocusOptions(): array
    {
        return [
            'brand_experience' => 'تجربه متفاوت و تصویر برند',
            'smart_tech' => 'فناوری و قابلیت هوشمند',
            'practical_info' => 'کاربرد واقعی و حفظ اطلاعات',
            'gift_memory' => 'تمایز و ماندگاری هدیه',
            'other' => 'سایر',
        ];
    }

    public static function decisionRoleOptions(): array
    {
        return [
            'decision_maker' => 'تصمیم‌گیرنده نهایی',
            'influencer' => 'تأثیرگذار / مشاور',
            'user' => 'کاربر / مصرف‌کننده',
            'introducer' => 'معرف / واسطه',
        ];
    }

    public static function lostReasonOptions(): array
    {
        return [
            'price' => 'قیمت',
            'competitor' => 'رقیب',
            'timing' => 'زمان‌بندی',
            'no_budget' => 'عدم بودجه',
            'no_need' => 'عدم نیاز',
            'no_response' => 'عدم پاسخ',
            'other' => 'سایر',
        ];
    }

    public static function productInterestOptions(): array
    {
        $lines = ProductLine::query()
            ->where('is_active', true)
            ->orderBy('sort')
            ->orderBy('name')
            ->get(['code', 'name']);

        $options = [];
        foreach ($lines as $line) {
            $code = trim((string) $line->code);
            if ($code === '') {
                continue;
            }
            $options[$code] = $line->name ?: $code;
        }

        if ($options === []) {
            $options = [
                'notopia' => 'نوتوپیا',
                'cheleva' => 'چلیوا',
            ];
        }

        return $options;
    }

    public function productInterestLabel(): string
    {
        $codes = $this->product_interest;
        if (! is_array($codes) || $codes === []) {
            return '—';
        }

        $map = static::productInterestOptions();
        $labels = [];
        foreach ($codes as $code) {
            $labels[] = $map[$code] ?? (string) $code;
        }

        return implode('، ', $labels);
    }

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }

    public function stage(): BelongsTo
    {
        return $this->belongsTo(OpportunityStage::class, 'stage_id');
    }

    public function owner(): BelongsTo
    {
        return $this->belongsTo(User::class, 'owner_user_id');
    }

    public function followupUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'followup_user_id');
    }

    public function activities(): HasMany
    {
        return $this->hasMany(Activity::class);
    }

    public function markWon(): void
    {
        $wonStage = OpportunityStage::query()
            ->where('is_won', true)
            ->where('is_active', true)
            ->orderBy('sort_order')
            ->first();

        if ($wonStage) {
            $this->stage_id = $wonStage->id;
        }

        $this->status = self::STATUS_WON;
        $this->won_at = now();
        $this->lost_at = null;
        $this->lost_reason = null;
        $this->lost_reason_code = null;
        $this->save();
    }

    public function markLost(?string $reason = null, ?string $code = null): void
    {
        $lostStage = OpportunityStage::query()
            ->where('is_lost', true)
            ->where('is_active', true)
            ->orderBy('sort_order')
            ->first();

        if ($lostStage) {
            $this->stage_id = $lostStage->id;
        }

        $this->status = self::STATUS_LOST;
        $this->lost_at = now();
        $this->won_at = null;
        $this->lost_reason = $reason;
        $this->lost_reason_code = $code;
        $this->save();
    }
}
