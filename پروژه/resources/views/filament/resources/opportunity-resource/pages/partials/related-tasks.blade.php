@forelse (($relatedTasks ?? collect()) as $task)
    <div class="opp-quote" style="cursor:default">
        <div class="opp-quote__top">
            <span class="opp-quote__num">{{ $task->subject }}</span>
            <span class="opp-quote__status">{{ \App\Models\Task::statusOptions()[$task->status] ?? $task->status }}</span>
        </div>
        <div class="opp-quote__title">
            {{ $task->assignee?->name ?? '—' }}
            @if ($task->due_at)
                · سررسید {{ \App\Support\Fa::dateTime($task->due_at, 'Y/m/d') }}
            @endif
        </div>
    </div>
@empty
    <div class="opp-empty" style="margin:0;border-radius:0;border-top:1px solid #f3f4f6">کار وابسته‌ای ثبت نشده. از دکمه «کار تیمی» بسازید.</div>
@endforelse
