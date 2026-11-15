<x-filament-panels::page>
    <div class="space-y-6" dir="rtl">
        <div class="grid grid-cols-2 gap-3 xl:grid-cols-4">
            <div class="rounded-xl border border-gray-200 bg-white p-4 shadow-sm dark:border-gray-700 dark:bg-gray-900">
                <div class="text-sm text-gray-500">فعالیت‌های قابل مشاهده</div>
                <div class="mt-2 text-2xl font-bold">{{ number_format($summary['activities'] ?? 0) }}</div>
            </div>
            <div class="rounded-xl border border-gray-200 bg-white p-4 shadow-sm dark:border-gray-700 dark:bg-gray-900">
                <div class="text-sm text-gray-500">کارهای قابل مشاهده</div>
                <div class="mt-2 text-2xl font-bold">{{ number_format($summary['tasks'] ?? 0) }}</div>
            </div>
            <div class="rounded-xl border border-red-200 bg-white p-4 shadow-sm dark:border-gray-700 dark:bg-gray-900">
                <div class="text-sm text-red-600">فعالیت‌های سررسیدگذشته</div>
                <div class="mt-2 text-2xl font-bold">{{ number_format($summary['overdueActivities'] ?? 0) }}</div>
            </div>
            <div class="rounded-xl border border-red-200 bg-white p-4 shadow-sm dark:border-gray-700 dark:bg-gray-900">
                <div class="text-sm text-red-600">کارهای سررسیدگذشته</div>
                <div class="mt-2 text-2xl font-bold">{{ number_format($summary['overdueTasks'] ?? 0) }}</div>
            </div>
        </div>

        <div class="flex flex-wrap items-end gap-3 rounded-xl border border-gray-200 bg-white p-4 dark:border-gray-700 dark:bg-gray-900">
            <label class="min-w-44 flex-1 text-sm">
                <span class="mb-1 block text-gray-600 dark:text-gray-300">مسئول</span>
                <select wire:model.live="ownerFilter" class="w-full rounded-lg border-gray-300 text-sm dark:border-gray-600 dark:bg-gray-800">
                    <option value="">همه افراد مجاز</option>
                    @foreach ($ownerOptions as $id => $name)
                        <option value="{{ $id }}">{{ $name }}</option>
                    @endforeach
                </select>
            </label>
            <label class="min-w-36 flex-1 text-sm">
                <span class="mb-1 block text-gray-600 dark:text-gray-300">وضعیت</span>
                <select wire:model.live="statusFilter" class="w-full rounded-lg border-gray-300 text-sm dark:border-gray-600 dark:bg-gray-800">
                    <option value="">همه وضعیت‌ها</option>
                    <option value="open">باز / در جریان</option>
                    <option value="done">انجام‌شده</option>
                    <option value="overdue">سررسیدگذشته</option>
                </select>
            </label>
            <label class="min-w-36 flex-1 text-sm">
                <span class="mb-1 block text-gray-600 dark:text-gray-300">نوع</span>
                <select wire:model.live="kindFilter" class="w-full rounded-lg border-gray-300 text-sm dark:border-gray-600 dark:bg-gray-800">
                    <option value="">فعالیت و کار</option>
                    <option value="activities">فعالیت‌های CRM</option>
                    <option value="tasks">کارهای مستقل</option>
                </select>
            </label>
            @if ($canCreateTask)
                <a href="{{ $taskCreateUrl }}" class="rounded-lg bg-primary-600 px-4 py-2 text-sm font-medium text-white">ثبت کار جدید</a>
            @endif
            @if ($canViewOpportunities)
                <a href="{{ $opportunityListUrl }}" class="rounded-lg border border-gray-300 px-4 py-2 text-sm dark:border-gray-600">فرصت‌های فروش</a>
            @endif
        </div>

        <div class="grid gap-6 xl:grid-cols-2">
            <section class="overflow-hidden rounded-xl border border-gray-200 bg-white dark:border-gray-700 dark:bg-gray-900">
                <div class="border-b border-gray-200 px-4 py-3 font-semibold dark:border-gray-700">فعالیت‌های CRM</div>
                <div class="divide-y divide-gray-100 dark:divide-gray-800">
                    @forelse ($activities as $activity)
                        <div class="flex items-start justify-between gap-3 p-4">
                            <div class="min-w-0">
                                <div class="truncate font-medium">{{ $activity->subject }}</div>
                                <div class="mt-1 text-xs text-gray-500">{{ $activity->user?->name }} · {{ $activity->opportunity?->title ?? 'بدون فرصت مرتبط' }}</div>
                            </div>
                            <div class="shrink-0 text-left text-xs">
                                <div>{{ $activity->due_at?->format('Y/m/d H:i') ?? 'بدون سررسید' }}</div>
                                <div class="mt-1 {{ $activity->is_done ? 'text-success-600' : 'text-gray-500' }}">{{ $activity->is_done ? 'انجام‌شده' : 'باز' }}</div>
                            </div>
                        </div>
                    @empty
                        <div class="p-6 text-center text-sm text-gray-500">فعالیتی در محدوده دسترسی پیدا نشد.</div>
                    @endforelse
                </div>
            </section>

            <section class="overflow-hidden rounded-xl border border-gray-200 bg-white dark:border-gray-700 dark:bg-gray-900">
                <div class="flex items-center justify-between border-b border-gray-200 px-4 py-3 dark:border-gray-700">
                    <span class="font-semibold">کارهای مستقل</span>
                    <a href="{{ $taskListUrl }}" class="text-sm text-primary-600">همه کارها</a>
                </div>
                <div class="divide-y divide-gray-100 dark:divide-gray-800">
                    @forelse ($tasks as $task)
                        <div class="flex items-start justify-between gap-3 p-4">
                            <div class="min-w-0">
                                <div class="truncate font-medium">{{ $task->subject }}</div>
                                <div class="mt-1 text-xs text-gray-500">{{ $task->assignee?->name }} · {{ \App\Models\Task::priorityOptions()[$task->priority] ?? $task->priority }}</div>
                            </div>
                            <div class="shrink-0 text-left text-xs">
                                <div>{{ $task->due_at?->format('Y/m/d H:i') ?? 'بدون سررسید' }}</div>
                                <div class="mt-1 text-gray-500">{{ \App\Models\Task::statusOptions()[$task->status] ?? $task->status }}</div>
                            </div>
                        </div>
                    @empty
                        <div class="p-6 text-center text-sm text-gray-500">کاری در محدوده دسترسی پیدا نشد.</div>
                    @endforelse
                </div>
            </section>
        </div>

        <section class="overflow-hidden rounded-xl border border-gray-200 bg-white dark:border-gray-700 dark:bg-gray-900">
            <div class="border-b border-gray-200 px-4 py-3 font-semibold dark:border-gray-700">تاریخچه اقدامات اخیر</div>
            <div class="divide-y divide-gray-100 dark:divide-gray-800">
                @forelse ($auditEvents as $event)
                    <div class="flex flex-wrap items-center justify-between gap-2 p-4 text-sm">
                        <div>
                            <span class="font-medium">{{ $event->actor?->name ?? 'سیستم' }}</span>
                            <span class="text-gray-500">{{ ['created' => 'ایجاد کرد', 'updated' => 'ویرایش کرد', 'deleted' => 'حذف کرد'][$event->event] ?? $event->event }}</span>
                            <span class="text-gray-500">{{ class_basename($event->auditable_type) === 'Task' ? 'یک کار' : 'یک فعالیت CRM' }}</span>
                        </div>
                        <time class="text-xs text-gray-500">{{ $event->occurred_at?->format('Y/m/d H:i') }}</time>
                        @if (! empty($event->new_values))
                            <div class="w-full text-xs text-gray-500">{{ collect($event->new_values)->map(fn ($value, $field) => $field . ': ' . (is_scalar($value) ? $value : json_encode($value)))->implode('، ') }}</div>
                        @endif
                    </div>
                @empty
                    <div class="p-6 text-center text-sm text-gray-500">هنوز رویداد ممیزی ثبت نشده است.</div>
                @endforelse
            </div>
        </section>
    </div>
</x-filament-panels::page>
