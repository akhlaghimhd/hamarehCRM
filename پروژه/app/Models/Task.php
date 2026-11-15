<?php

namespace App\\Models;

use App\\Models\\Concerns\\BelongsToCompany;
use Illuminate\\Database\\Eloquent\\Model;
use Illuminate\\Database\\Eloquent\\Relations\\BelongsTo;
use Illuminate\\Database\\Eloquent\\Relations\\MorphMany;
use Illuminate\\Database\\Eloquent\\Relations\\MorphTo;
use Illuminate\\Database\\Eloquent\\SoftDeletes;
use Illuminate\\Support\\Facades\\DB;
use Illuminate\\Validation\\ValidationException;

class Task extends Model
{
    use BelongsToCompany;
    use SoftDeletes;

    public const STATUS_OPEN = 'open';
    public const STATUS_IN_PROGRESS = 'in_progress';
    public const STATUS_DONE = 'done';
    public const STATUS_CANCELLED = 'cancelled';
    public const PRIORITY_LOW = 'low';
    public const PRIORITY_NORMAL = 'normal';
    public const PRIORITY_HIGH = 'high';
    public const PRIORITY_URGENT = 'urgent';

    protected $attributes = ['status' => self::STATUS_OPEN, 'priority' => self::PRIORITY_NORMAL];

    protected $fillable = [
        'company_id', 'subject', 'description', 'assignee_user_id', 'reporter_user_id',
        'priority', 'status', 'due_at', 'linkable_type', 'linkable_id', 'idempotency_key', 'completed_at',
    ];

    protected function casts(): array
    {
        return ['due_at' => 'datetime', 'completed_at' => 'datetime'];
    }

    protected static function booted(): void
    {
        static::saving(function (Task $task): void {
            if (! $task->company_id || ! $task->assignee_user_id || ! $task->reporter_user_id) {
                throw ValidationException::withMessages(['subject' => 'شرکت، مسئول و ایجادکننده کار الزامی است.']);
            }

            foreach ([$task->assignee_user_id, $task->reporter_user_id] as $userId) {
                $member = DB::table('company_user')->where('company_id', $task->company_id)->where('user_id', $userId)->exists();
                if (! $member) {
                    throw ValidationException::withMessages(['assignee_user_id' => 'مسئول و ایجادکننده باید عضو همان شرکت باشند.']);
                }
            }

            if (! array_key_exists($task->status, self::statusOptions()) || ! array_key_exists($task->priority, self::priorityOptions())) {
                throw ValidationException::withMessages(['status' => 'وضعیت یا اولویت کار معتبر نیست.']);
            }
        });
    }

    public static function statusOptions(): array
    {
        return [
            self::STATUS_OPEN => 'باز',
            self::STATUS_IN_PROGRESS => 'در حال انجام',
            self::STATUS_DONE => 'انجام‌شده',
            self::STATUS_CANCELLED => 'لغوشده',
        ];
    }

    public static function priorityOptions(): array
    {
        return [
            self::PRIORITY_LOW => 'کم',
            self::PRIORITY_NORMAL => 'عادی',
            self::PRIORITY_HIGH => 'زیاد',
            self::PRIORITY_URGENT => 'فوری',
        ];
    }

    public function assignee(): BelongsTo
    {
        return $this->belongsTo(User::class, 'assignee_user_id');
    }

    public function reporter(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reporter_user_id');
    }

    public function linkable(): MorphTo
    {
        return $this->morphTo();
    }

    public function auditEvents(): MorphMany
    {
        return $this->morphMany(AuditEvent::class, 'auditable')->latest('occurred_at');
    }

    public function markDone(): void
    {
        $this->forceFill(['status' => self::STATUS_DONE, 'completed_at' => now()])->save();
    }
}
