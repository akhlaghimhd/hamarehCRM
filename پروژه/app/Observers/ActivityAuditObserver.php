<?php

namespace App\Observers;

use App\Models\Activity;
use App\Models\AuditEvent;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Auth;
use WeakMap;

class ActivityAuditObserver
{
    private const FIELDS = ['opportunity_id', 'opportunity_step_id', 'user_id', 'type', 'subject', 'body', 'due_at', 'completed_at', 'is_done'];
    private static ?WeakMap $before = null;

    public function updating(Activity $activity): void
    {
        self::$before ??= new WeakMap();
        self::$before[$activity] = Arr::only($activity->getOriginal(), self::FIELDS);
    }

    public function created(Activity $activity): void
    {
        $this->record($activity, 'created', [], Arr::only($activity->getAttributes(), self::FIELDS));
    }

    public function updated(Activity $activity): void
    {
        $new = Arr::only($activity->getChanges(), self::FIELDS);
        if ($new !== []) {
            $old = self::$before[$activity] ?? [];
            unset(self::$before[$activity]);
            $this->record($activity, 'updated', Arr::only($old, array_keys($new)), $new);
        }
    }

    public function deleted(Activity $activity): void
    {
        $this->record($activity, 'deleted', Arr::only($activity->getAttributes(), self::FIELDS), []);
    }

    private function record(Activity $activity, string $event, array $old, array $new): void
    {
        AuditEvent::query()->create([
            'company_id' => $activity->company_id,
            'auditable_type' => $activity->getMorphClass(),
            'auditable_id' => $activity->getKey(),
            'actor_user_id' => Auth::id(),
            'event' => $event,
            'old_values' => $old ?: null,
            'new_values' => $new ?: null,
            'occurred_at' => now(),
        ]);
    }
}
