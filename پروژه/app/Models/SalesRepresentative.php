<?php

namespace App\Models;

use App\Models\Concerns\BelongsToCompany;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use InvalidArgumentException;

class SalesRepresentative extends Model
{
    use BelongsToCompany;
    use SoftDeletes;

    public const TYPE_INTERNAL = 'internal';
    public const TYPE_EXTERNAL = 'external';

    public const STATUS_ACTIVE = 'active';
    public const STATUS_INACTIVE = 'inactive';

    protected $fillable = [
        'company_id',
        'type',
        'code',
        'name',
        'user_id',
        'customer_id',
        'parent_id',
        'status',
        'mobile',
        'email',
        'started_on',
        'ended_on',
        'notes',
    ];

    protected function casts(): array
    {
        return [
            'started_on' => 'date',
            'ended_on' => 'date',
        ];
    }

    protected static function booted(): void
    {
        static::saving(function (SalesRepresentative $rep): void {
            if (! array_key_exists($rep->type, self::typeOptions())) {
                throw new InvalidArgumentException('نوع نماینده معتبر نیست.');
            }

            if ($rep->type === self::TYPE_INTERNAL && empty($rep->user_id)) {
                throw new InvalidArgumentException('نماینده داخلی باید به کاربر عضو شرکت متصل باشد.');
            }

            if ($rep->type === self::TYPE_EXTERNAL && empty($rep->customer_id) && empty($rep->name)) {
                throw new InvalidArgumentException('نماینده بیرونی نیاز به نام دارد.');
            }
        });
    }

    public static function typeOptions(): array
    {
        return [
            self::TYPE_INTERNAL => 'داخلی',
            self::TYPE_EXTERNAL => 'بیرونی',
        ];
    }

    public static function statusOptions(): array
    {
        return [
            self::STATUS_ACTIVE => 'فعال',
            self::STATUS_INACTIVE => 'غیرفعال',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }

    public function parent(): BelongsTo
    {
        return $this->belongsTo(self::class, 'parent_id');
    }

    public function children(): HasMany
    {
        return $this->hasMany(self::class, 'parent_id');
    }

    public function isActive(): bool
    {
        if ($this->status !== self::STATUS_ACTIVE) {
            return false;
        }

        if ($this->ended_on && $this->ended_on->isPast()) {
            return false;
        }

        return true;
    }

    public function toAttributionSnapshot(): array
    {
        return [
            'sales_representative_id' => $this->id,
            'type' => $this->type,
            'code' => $this->code,
            'name' => $this->name,
            'user_id' => $this->user_id,
            'customer_id' => $this->customer_id,
            'captured_at' => now()->toIso8601String(),
        ];
    }
}
