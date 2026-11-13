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

    protected $fillable = [
        'company_id',
        'customer_id',
        'stage_id',
        'owner_user_id',
        'title',
        'amount',
        'probability',
        'source',
        'notes',
        'lost_reason',
        'won_at',
        'lost_at',
    ];

    protected function casts(): array
    {
        return [
            'amount' => 'decimal:0',
            'probability' => 'integer',
            'won_at' => 'datetime',
            'lost_at' => 'datetime',
        ];
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

        $this->won_at = now();
        $this->lost_at = null;
        $this->lost_reason = null;
        $this->save();
    }

    public function markLost(?string $reason = null): void
    {
        $lostStage = OpportunityStage::query()
            ->where('is_lost', true)
            ->where('is_active', true)
            ->orderBy('sort_order')
            ->first();

        if ($lostStage) {
            $this->stage_id = $lostStage->id;
        }

        $this->lost_at = now();
        $this->won_at = null;
        $this->lost_reason = $reason;
        $this->save();
    }
}
