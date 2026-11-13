<?php

namespace App\Models;

use App\Models\Concerns\BelongsToCompany;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class Activity extends Model
{
    use BelongsToCompany;
    use SoftDeletes;

    public const TYPE_CALL = 'call';
    public const TYPE_MEETING = 'meeting';
    public const TYPE_NOTE = 'note';
    public const TYPE_TASK = 'task';

    protected $fillable = [
        'company_id',
        'opportunity_id',
        'user_id',
        'type',
        'subject',
        'body',
        'due_at',
        'completed_at',
        'is_done',
    ];

    protected function casts(): array
    {
        return [
            'due_at' => 'datetime',
            'completed_at' => 'datetime',
            'is_done' => 'boolean',
        ];
    }

    public static function typeOptions(): array
    {
        return [
            self::TYPE_CALL => 'تماس',
            self::TYPE_MEETING => 'جلسه',
            self::TYPE_NOTE => 'یادداشت',
            self::TYPE_TASK => 'وظیفه',
        ];
    }

    public function opportunity(): BelongsTo
    {
        return $this->belongsTo(Opportunity::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function markDone(): void
    {
        $this->is_done = true;
        $this->completed_at = now();
        $this->save();
    }
}
