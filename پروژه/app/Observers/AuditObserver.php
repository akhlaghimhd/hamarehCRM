<?php

namespace App\\Observers;

use App\\Models\\AuditEvent;
use Illuminate\\Database\\Eloquent\\Model;
use Illuminate\\Support\\Arr;
use Illuminate\\Support\\Facades\\Auth;
use WeakMap;

class AuditObserver
{
    private const FIELDS = [
        'subject', 'description', 'assignee_user_id', 'priority', 'status',
        'due_at', 'completed_at', 'is_done', 'user_id', 'type', 'body',
        'opportunity_id', 'opportunity_step_id',
    ];

    private static ?WeakMap $before = null;

    public function updating(Model $model): void
    {
        self::$before ??= new WeakMap();
        self::$before[$model] = Arr::only($model->getOriginal(), self::FIELDS);
    }

    public function created(Model $model): void
    {
        $this->record($model, 'created', [], Arr::only($model->getAttributes(), self::FIELDS));
    }

    public function updated(Model $model): void
    {
        $new = Arr::only($model->getChanges(), self::FIELDS);
        if ($new === []) {
            return;
        }
        $old = self::$before[$model] ?? [];
        unset(self::$before[$model]);
        $this->record($model, 'updated', Arr::only($old, array_keys($new)), $new);
    }

    public function deleted(Model $model): void
    {
        $this->record($model, 'deleted', Arr::only($model->getAttributes(), self::FIELDS), []);
    }

    private function record(Model $model, string $event, array $old, array $new): void
    {
        if (! $model->getAttribute('company_id') || ! $model->getKey()) {
            return;
        }
        AuditEvent::query()->create([
            'company_id' => $model->getAttribute('company_id'),
            'auditable_type' => $model->getMorphClass(),
            'auditable_id' => $model->getKey(),
            'actor_user_id' => Auth::id(),
            'event' => $event,
            'old_values' => $old ?: null,
            'new_values' => $new ?: null,
            'occurred_at' => now(),
        ]);
    }
}
