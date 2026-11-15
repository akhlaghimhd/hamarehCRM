<?php

namespace App\Observers;

use App\Models\Activity;
use App\Models\AuditEvent;
use App\Models\Task;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Auth;
use WeakMap;

class AuditObserver
{
    /**
     * Only explicitly approved business fields are persisted in the audit trail.
     *
     * @var array<class-string<Model>, list<string>>
     */
    private const FIELDS = [
        Task::class => [
            'subject', 'description', 'assignee_user_id', 'reporter_user_id',
            'priority', 'status', 'due_at', 'completed_at',
            'linkable_type', 'linkable_id',
        ],
        Activity::class => [
            'opportunity_id', 'opportunity_step_id', 'user_id', 'type',
            'subject', 'body', 'due_at', 'completed_at', 'is_done',
        ],
    ];

    /** @var WeakMap<Model, array<string, mixed>>|null */
    private static ?WeakMap $before = null;

    public function updating(Model $model): void
    {
        self::$before ??= new WeakMap();
        self::$before[$model] = Arr::only($model->getOriginal(), self::fieldsFor($model));
    }

    public function created(Model $model): void
    {
        $this->record($model, 'created', [], Arr::only($model->getAttributes(), self::fieldsFor($model)));
    }

    public function updated(Model $model): void
    {
        $new = Arr::only($model->getChanges(), self::fieldsFor($model));

        if ($new === []) {
            unset(self::$before[$model]);
            return;
        }

        $old = self::$before[$model] ?? [];
        unset(self::$before[$model]);

        $this->record($model, 'updated', Arr::only($old, array_keys($new)), $new);
    }

    public function deleted(Model $model): void
    {
        $this->record($model, 'deleted', Arr::only($model->getAttributes(), self::fieldsFor($model)), []);
    }

    private static function fieldsFor(Model $model): array
    {
        return self::FIELDS[$model::class] ?? [];
    }

    private function record(Model $model, string $event, array $old, array $new): void
    {
        $companyId = $model->getAttribute('company_id');

        if (! $companyId || ! $model->getKey()) {
            return;
        }

        AuditEvent::query()->create([
            'company_id' => $companyId,
            'auditable_type' => $model->getMorphClass(),
            'auditable_id' => $model->getKey(),
            'actor_user_id' => Auth::id(),
            'event' => $event,
            'old_values' => $old ?: null,
            'new_values' => $new ?: null,
            'metadata' => ['source' => 'eloquent_observer'],
            'occurred_at' => now(),
        ]);
    }
}
