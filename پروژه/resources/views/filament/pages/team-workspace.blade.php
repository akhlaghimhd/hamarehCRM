<x-filament-panels::page>
    <div class="space-y-5" dir="rtl">
        <section class="grid grid-cols-2 gap-3 xl:grid-cols-4">
            @foreach ([
                ['فعالیت‌های قابل مشاهده', $summary['activities'] ?? 0, 'فعالیت CRM'],
                ['کارهای قابل مشاهده', $summary['tasks'] ?? 0, 'کارهای واگذارشده'],
                ['فعالیت‌های سررسیدگذشته', $summary['overdueActivities'] ?? 0, 'نیازمند رسیدگی'],
                ['کارهای سررسیدگذشته', $summary['overdueTasks'] ?? 0, 'نیازمند اقدام'],
            ] as [$label, $value, $hint])
                <article class="rounded-2xl border border-gray-200 bg-white p-4 shadow-sm dark:border-gray-700 dark:bg-gray-900">
                    <p class="text-xs font-medium text-gray-500 dark:text-gray-400">{{ $label }}</p>
                    <p class="mt-2 text-2xl font-bold tabular-nums text-gray-950 dark:text-white">{{ number_format($value) }}</p>
                    <p class="mt-1 text-xs text-gray-400">{{ $hint }}</p>
                </article>
            @endforeach
        </section>

        <section class="rounded-2xl border border-gray-200 bg-white p-4 shadow-sm dark:border-gray-700 dark:bg-gray-900">
            <div class="mb-4">
                <h2 class="font-bold text-gray-900 dark:text-white">فیلتر فعالیت‌های تیم</h2>
                <p class="mt-1 text-xs text-gray-500">اطلاعات فقط در محدوده دسترسی شما نمایش داده می‌شود.</p>
            </div>
            <div class="grid gap-3 sm:grid-cols-2 xl:grid-cols-4">
                <label class="text-xs font-semibold text-gray-600 dark:text-gray-300">مسئول
                    <select wire:model.live="ownerFilter" class="mt-2 min-h-11 w-full rounded-xl border-gray-200 bg-white text-sm dark:border-gray-700 dark:bg-gray-800">
                        <option value="">همه افراد مجاز</option>
                        @foreach ($ownerOptions as $id => $name) <option value="{{ $id }}">{{ $name }}</option> @endforeach
                    </select>
                </label>
                <label class="text-xs font-semibold text-gray-600 dark:text-gray-300">وضعیت
                    <select wire:model.live="statusFilter" class="mt-2 min-h-11 w-full rounded-xl border-gray-200 bg-white text-sm dark:border-gray-700 dark:bg-gray-800">
                        <option value="">همه وضعیت‌ها</option><option value="open">باز / در جریان</option><option value="done">انجام‌شده</option><option value="overdue">سررسیدگذشته</option>
                    </select>
                </label>
                <label class="text-xs font-semibold text-gray-600 dark:text-gray-300">نوع
                    <select wire:model.live="kindFilter" class="mt-2 min-h-11 w-full rounded-xl border-gray-200 bg-white text-sm dark:border-gray-700 dark:bg-gray-800">
                        <option value="">فعالیت و کار</option><option value="activities">فعالیت‌های CRM</option><option value="tasks">کارهای مستقل</option>
                    </select>
                </label>
                <div class="flex items-end gap-2">
                    @if ($canCreateTask) <a href="{{ $taskCreateUrl }}" class="inline-flex min-h-11 flex-1 items-center justify-center rounded-xl bg-primary-600 px-3 text-xs font-semibold text-white">ثبت کار جدید</a> @endif
                    @if ($canViewOpportunities) <a href="{{ $opportunityListUrl }}" class="inline-flex min-h-11 flex-1 items-center justify-center rounded-xl border border-gray-200 px-3 text-xs font-semibold dark:border-gray-700">فرصت‌ها</a> @endif
                </div>
            </div>
        </section>

        <div class="grid gap-4 xl:grid-cols-2">
            <section class="overflow-hidden rounded-2xl border border-gray-200 bg-white shadow-sm dark:border-gray-700 dark:bg-gray-900">
                <header class="border-b border-gray-100 px-4 py-4 dark:border-gray-800"><h2 class="font-bold text-gray-900 dark:text-white">فعالیت‌های CRM</h2><p class="mt-1 text-xs text-gray-500">آخرین پیگیری‌های ثبت‌شده</p></header>
                <div class="divide-y divide-gray-100 dark:divide-gray-800">
                    @forelse ($activities as $activity)
                        <article class="flex flex-col gap-2 p-4 sm:flex-row sm:items-center sm:justify-between">
                            <div class="min-w-0"><p class="font-semibold text-gray-900 dark:text-white">{{ $activity->subject }}</p><p class="mt-1 text-xs text-gray-500">{{ $activity->user?->name }} · {{ $activity->opportunity?->title ?? 'بدون فرصت مرتبط' }}</p></div>
                            <div class="flex shrink-0 items-center justify-between gap-3 text-xs sm:block sm:text-left"><time>{{ $activity->due_at?->format('Y/m/d H:i') ?? 'بدون سررسید' }}</time><span class="{{ $activity->is_done ? 'text-success-600' : 'text-gray-500' }}">{{ $activity->is_done ? 'انجام‌شده' : 'باز' }}</span></div>
                        </article>
                    @empty <div class="p-8 text-center text-sm text-gray-400">فعالیتی در محدوده دسترسی پیدا نشد.</div> @endforelse
                </div>
            </section>
            <section class="overflow-hidden rounded-2xl border border-gray-200 bg-white shadow-sm dark:border-gray-700 dark:bg-gray-900">
                <header class="flex items-center justify-between border-b border-gray-100 px-4 py-4 dark:border-gray-800"><div><h2 class="font-bold text-gray-900 dark:text-white">کارهای مستقل</h2><p class="mt-1 text-xs text-gray-500">کارهای واگذارشده به تیم</p></div><a href="{{ $taskListUrl }}" class="text-xs font-semibold text-primary-700 dark:text-primary-300">مشاهده همه</a></header>
                <div class="divide-y divide-gray-100 dark:divide-gray-800">
                    @forelse ($tasks as $task)
                        <article class="flex flex-col gap-2 p-4 sm:flex-row sm:items-center sm:justify-between"><div class="min-w-0"><p class="font-semibold text-gray-900 dark:text-white">{{ $task->subject }}</p><p class="mt-1 text-xs text-gray-500">{{ $task->assignee?->name }} · {{ \App\Models\Task::priorityOptions()[$task->priority] ?? $task->priority }}</p></div><div class="flex shrink-0 items-center justify-between gap-3 text-xs sm:block sm:text-left"><time>{{ $task->due_at?->format('Y/m/d H:i') ?? 'بدون سررسید' }}</time><span class="text-gray-500">{{ \App\Models\Task::statusOptions()[$task->status] ?? $task->status }}</span></div></article>
                    @empty <div class="p-8 text-center text-sm text-gray-400">کاری در محدوده دسترسی پیدا نشد.</div> @endforelse
                </div>
            </section>
        </div>

        <section class="overflow-hidden rounded-2xl border border-gray-200 bg-white shadow-sm dark:border-gray-700 dark:bg-gray-900">
            <header class="border-b border-gray-100 px-4 py-4 dark:border-gray-800"><h2 class="font-bold text-gray-900 dark:text-white">تاریخچه اقدامات اخیر</h2><p class="mt-1 text-xs text-gray-500">رویدادهای قابل مشاهده در محدوده شرکت و تیم</p></header>
            <div class="divide-y divide-gray-100 dark:divide-gray-800">
                @forelse ($auditEvents as $event)
                    <article class="space-y-2 p-4 text-sm"><div class="flex flex-wrap items-center justify-between gap-2"><p><span class="font-semibold text-gray-900 dark:text-white">{{ $event->actor?->name ?? 'سیستم' }}</span> <span class="text-gray-500">{{ ['created' => 'ایجاد کرد', 'updated' => 'ویرایش کرد', 'deleted' => 'حذف کرد'][$event->event] ?? $event->event }}</span> <span class="text-gray-500">{{ class_basename($event->auditable_type) === 'Task' ? 'یک کار' : 'یک فعالیت CRM' }}</span></p><time class="text-xs text-gray-400">{{ $event->occurred_at?->format('Y/m/d H:i') }}</time></div>
                    @if (! empty($event->new_values)) <p class="text-xs leading-6 text-gray-500">{{ collect($event->new_values)->map(fn ($value, $field) => $field . ': ' . (is_scalar($value) ? $value : json_encode($value)))->implode('، ') }}</p> @endif</article>
                @empty <div class="p-8 text-center text-sm text-gray-400">هنوز رویداد ممیزی ثبت نشده است.</div> @endforelse
            </div>
        </section>
    </div>
</x-filament-panels::page>
