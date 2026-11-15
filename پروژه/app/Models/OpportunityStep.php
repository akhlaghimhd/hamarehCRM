<?php

namespace App\Models;

use App\Models\Concerns\BelongsToCompany;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class OpportunityStep extends Model
{
    use BelongsToCompany;
    use SoftDeletes;

    protected $fillable = [
        'company_id',
        'opportunity_id',
        'assigned_user_id',
        'title',
        'due_at',
        'result',
        'closed_at',
        'is_open',
        'sort_order',
    ];

    protected function casts(): array
    {
        return [
            'due_at' => 'date',
            'closed_at' => 'datetime',
            'is_open' => 'boolean',
            'sort_order' => 'integer',
        ];
    }

    public function opportunity(): BelongsTo
    {
        return $this->belongsTo(Opportunity::class);
    }

    public function assignedUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'assigned_user_id');
    }

    public function activities(): HasMany
    {
        return $this->hasMany(Activity::class, 'opportunity_step_id');
    }

    public function close(string $result): void
    {
        $this->result = $result;
        $this->is_open = false;
        $this->closed_at = now();
        $this->save();
    }
}
