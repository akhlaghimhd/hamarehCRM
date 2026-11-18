<?php

namespace App\Models;

use App\Models\Concerns\BelongsToCompany;
use App\Support\NumberSequence;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use InvalidArgumentException;

class WorkPackage extends Model
{
    use BelongsToCompany;
    use SoftDeletes;

    public const MODE_INTERNAL = 'internal';
    public const MODE_OUTSOURCED = 'outsourced';
    public const MODE_HYBRID = 'hybrid';

    public const STATUS_DRAFT = 'draft';
    public const STATUS_ASSIGNED = 'assigned';
    public const STATUS_IN_PROGRESS = 'in_progress';
    public const STATUS_DELIVERED = 'delivered';
    public const STATUS_ACCEPTED = 'accepted';
    public const STATUS_REJECTED = 'rejected';
    public const STATUS_CANCELLED = 'cancelled';

    protected $fillable = [
        'company_id',
        'order_id',
        'order_line_id',
        'number',
        'title',
        'description',
        'execution_mode',
        'status',
        'contractor_name',
        'assignee_user_id',
        'due_at',
        'notes',
    ];

    protected function casts(): array
    {
        return [
            'due_at' => 'datetime',
        ];
    }

    public static function executionModeOptions(): array
    {
        return [
            self::MODE_INTERNAL => 'داخلی',
            self::MODE_OUTSOURCED => 'برون‌سپاری',
            self::MODE_HYBRID => 'ترکیبی',
        ];
    }

    public static function statusOptions(): array
    {
        return [
            self::STATUS_DRAFT => 'پیش‌نویس',
            self::STATUS_ASSIGNED => 'ارجاع‌شده',
            self::STATUS_IN_PROGRESS => 'در حال انجام',
            self::STATUS_DELIVERED => 'تحویل‌شده',
            self::STATUS_ACCEPTED => 'پذیرش',
            self::STATUS_REJECTED => 'رد',
            self::STATUS_CANCELLED => 'لغو',
        ];
    }

    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class);
    }

    public function orderLine(): BelongsTo
    {
        return $this->belongsTo(OrderLine::class);
    }

    public function assignee(): BelongsTo
    {
        return $this->belongsTo(User::class, 'assignee_user_id');
    }

    public static function nextNumber(int $companyId): string
    {
        $year = now()->format('Y');

        return app(NumberSequence::class)->next($companyId, 'work_package', 'WP-'.$year.'-');
    }

    public static function createForOrder(Order $order, array $attributes = []): self
    {
        if (in_array($order->status, [Order::STATUS_CANCELLED, Order::STATUS_COMPLETED], true)) {
            throw new InvalidArgumentException('برای سفارش لغو یا تکمیل‌شده نمی‌توان بسته کاری ساخت.');
        }

        $title = $attributes['title'] ?? ('بسته کاری سفارش '.($order->number ?: '#'.$order->id));

        $package = new self([
            'company_id' => $order->company_id,
            'order_id' => $order->id,
            'order_line_id' => $attributes['order_line_id'] ?? null,
            'number' => self::nextNumber((int) $order->company_id),
            'title' => $title,
            'description' => $attributes['description'] ?? null,
            'execution_mode' => $attributes['execution_mode'] ?? self::MODE_INTERNAL,
            'status' => self::STATUS_DRAFT,
            'contractor_name' => $attributes['contractor_name'] ?? null,
            'assignee_user_id' => $attributes['assignee_user_id'] ?? null,
            'due_at' => $attributes['due_at'] ?? null,
            'notes' => $attributes['notes'] ?? null,
        ]);
        $package->save();

        return $package;
    }

    public function assign(): self
    {
        if (! in_array($this->status, [self::STATUS_DRAFT, self::STATUS_REJECTED], true)) {
            throw new InvalidArgumentException('فقط بسته پیش‌نویس یا ردشده قابل ارجاع است.');
        }

        $this->status = self::STATUS_ASSIGNED;
        $this->save();

        return $this;
    }

    public function start(): self
    {
        if (! in_array($this->status, [self::STATUS_ASSIGNED, self::STATUS_DRAFT], true)) {
            throw new InvalidArgumentException('شروع فقط از وضعیت ارجاع‌شده یا پیش‌نویس مجاز است.');
        }

        $this->status = self::STATUS_IN_PROGRESS;
        $this->save();

        return $this;
    }

    public function markDelivered(): self
    {
        if ($this->status !== self::STATUS_IN_PROGRESS) {
            throw new InvalidArgumentException('فقط بسته در حال انجام قابل ثبت تحویل است.');
        }

        $this->status = self::STATUS_DELIVERED;
        $this->save();

        return $this;
    }

    public function accept(): self
    {
        if ($this->status !== self::STATUS_DELIVERED) {
            throw new InvalidArgumentException('فقط تحویل‌شده قابل پذیرش است.');
        }

        $this->status = self::STATUS_ACCEPTED;
        $this->save();

        return $this;
    }

    public function reject(?string $reason = null): self
    {
        if ($this->status !== self::STATUS_DELIVERED) {
            throw new InvalidArgumentException('فقط تحویل‌شده قابل رد است.');
        }

        $this->status = self::STATUS_REJECTED;

        if (filled($reason)) {
            $note = trim((string) $this->notes);
            $addition = 'رد: '.trim($reason);
            $this->notes = $note === '' ? $addition : $note."\n".$addition;
        }

        $this->save();

        return $this;
    }

    public function cancel(?string $reason = null): self
    {
        if (in_array($this->status, [self::STATUS_ACCEPTED, self::STATUS_CANCELLED], true)) {
            throw new InvalidArgumentException('این بسته قابل لغو نیست.');
        }

        $this->status = self::STATUS_CANCELLED;

        if (filled($reason)) {
            $note = trim((string) $this->notes);
            $addition = 'لغو: '.trim($reason);
            $this->notes = $note === '' ? $addition : $note."\n".$addition;
        }

        $this->save();

        return $this;
    }
}
