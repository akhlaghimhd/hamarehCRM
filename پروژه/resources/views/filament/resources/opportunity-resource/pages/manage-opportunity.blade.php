<x-filament-panels::page>
    @php
        /** @var \App\Models\Opportunity $opportunity */
        /** @var \App\Models\OpportunityStep|null $openStep */
        $dash = '—';
        $hasOpen = $openStep !== null;
    @endphp

    <div class="fi-opp-workspace space-y-4" dir="rtl">
        {{-- Title + status --}}
        <div class="flex flex-col gap-2 sm:flex-row sm:items-start sm:justify-between">
            <div class="min-w-0">
                <h1 class="text-xl font-bold tracking-tight text-gray-950 dark:text-white">
                    {{ $pageTitle }}
                </h1>
                <div class="mt-2 flex flex-wrap items-center gap-1.5">
                    <span class="inline-flex items-center rounded-md bg-gray-100 px-2 py-0.5 text-xs font-medium text-gray-700 dark:bg-white/10 dark:text-gray-200">
                        {{ \App\Support\Fa::digits($codeLabel) }}
                    </span>
                    <span class="inline-flex items-center rounded-md bg-primary-50 px-2 py-0.5 text-xs font-medium text-primary-700 dark:bg-primary-400/10 dark:text-primary-300">
                        {{ $statusLabel }}
                    </span>
                    <span class="inline-flex items-center rounded-md bg-amber-50 px-2 py-0.5 text-xs font-medium text-amber-800 dark:bg-amber-400/10 dark:text-amber-300">
                        {{ $stageLabel }}
                    </span>
                    @if ($productLabel && $productLabel !== '—')
                        <span class="inline-flex items-center rounded-md bg-gray-50 px-2 py-0.5 text-xs text-gray-600 dark:bg-white/5 dark:text-gray-300">
                            {{ $productLabel }}
                        </span>
                    @endif
                    @if ($dueLabel)
                        <span @class([
                            'inline-flex items-center gap-1 rounded-md px-2 py-0.5 text-xs font-medium',
                            'bg-danger-50 text-danger-700 dark:bg-danger-400/10 dark:text-danger-300' => $isOverdue,
                            'bg-gray-50 text-gray-600 dark:bg-white/5 dark:text-gray-300' => ! $isOverdue,
                        ])>
                            @if ($isOverdue)
                                <x-filament::icon icon="heroicon-m-exclamation-triangle" class="h-3.5 w-3.5" />
                            @else
                                <x-filament::icon icon="heroicon-m-calendar" class="h-3.5 w-3.5" />
                            @endif
                            مهلت {{ \App\Support\Fa::digits($dueLabel) }}
                            @if ($isOverdue)
                                · عقب‌افتاده
                            @endif
                        </span>
                    @endif
                </div>
            </div>
        </div>

        <div class="grid grid-cols-1 items-start gap-4 lg:grid-cols-12 lg:gap-5">
            {{-- MAIN (در RTL سمت راست) --}}
            <div class="flex flex-col gap-4 lg:col-span-8">
                {{-- گام جاری --}}
                <section @class([
                    'overflow-hidden rounded-xl border bg-white shadow-sm dark:bg-gray-900',
                    'border-primary-300 dark:border-primary-500/40' => $hasOpen && ! $isOverdue,
                    'border-danger-300 dark:border-danger-500/40' => $hasOpen && $isOverdue,
                    'border-gray-200 dark:border-white/10' => ! $hasOpen,
                ])>
                    @if ($hasOpen)
                        {{-- نوار بالای کارت --}}
                        <div @class([
                            'flex flex-wrap items-center justify-between gap-2 border-b px-4 py-2.5',
                            'border-primary-100 bg-primary-50 dark:border-primary-500/20 dark:bg-primary-500/10' => ! $isOverdue,
                            'border-danger-100 bg-danger-50 dark:border-danger-500/20 dark:bg-danger-500/10' => $isOverdue,
                        ])>
                            <div class="flex flex-wrap items-center gap-2">
                                <span @class([
                                    'inline-flex h-6 items-center rounded-full px-2.5 text-[11px] font-semibold text-white',
                                    'bg-primary-600 dark:bg-primary-500' => ! $isOverdue,
                                    'bg-danger-600 dark:bg-danger-500' => $isOverdue,
                                ])>
                                    گام جاری
                                </span>
                                @if ($assigneeLabel && $assigneeLabel !== '—')
                                    <span class="text-xs text-gray-600 dark:text-gray-300">
                                        مسئول: <span class="font-medium">{{ $assigneeLabel }}</span>
                                    </span>
                                @endif
                            </div>
                            <div class="flex flex-wrap items-center gap-2">
                                {{ $this->closeStepAction }}
                                {{ $this->clearStepAction }}
                            </div>
                        </div>

                        {{-- عنوان و مهلت --}}
                        <div class="border-b border-gray-100 px-4 py-3 dark:border-white/10">
                            <h2 class="text-base font-bold leading-snug text-gray-950 dark:text-white sm:text-lg">
                                {{ $openStep->title }}
                            </h2>
                            @if ($dueLabel)
                                <p @class([
                                    'mt-1 text-sm',
                                    'font-semibold text-danger-700 dark:text-danger-300' => $isOverdue,
                                    'text-gray-500 dark:text-gray-400' => ! $isOverdue,
                                ])>
                                    مهلت {{ \App\Support\Fa::digits($dueLabel) }}
                                    @if ($isOverdue)
                                        — عقب‌افتاده
                                    @endif
                                </p>
                            @endif
                        </div>

                        {{-- فعالیت‌ها --}}
                        <div class="px-4 py-3">
                            <div class="mb-2.5 flex flex-wrap items-center justify-between gap-2">
                                <h3 class="text-sm font-semibold text-gray-800 dark:text-gray-100">
                                    فعالیت‌های این گام
                                    @if ($stepActivities->isNotEmpty())
                                        <span class="mr-1 text-xs font-normal text-gray-400">
                                            ({{ \App\Support\Fa::digits((string) $stepActivities->count()) }})
                                        </span>
                                    @endif
                                </h3>
                                {{ $this->logActivityAction }}
                            </div>

                            @if ($stepActivities->isEmpty())
                                <div class="rounded-lg border border-dashed border-gray-200 bg-gray-50/80 px-3 py-6 text-center dark:border-white/10 dark:bg-white/5">
                                    <p class="text-sm text-gray-500 dark:text-gray-400">هنوز فعالیتی ثبت نشده.</p>
                                    <p class="mt-0.5 text-xs text-gray-400">از دکمه «ثبت فعالیت» برای تماس، جلسه یا یادداشت استفاده کنید.</p>
                                </div>
                            @else
                                <ul class="divide-y divide-gray-100 overflow-hidden rounded-lg border border-gray-100 dark:divide-white/5 dark:border-white/10">
                                    @foreach ($stepActivities as $activity)
                                        <li class="bg-white px-3 py-2.5 dark:bg-gray-900/40">
                                            <div class="flex flex-wrap items-center justify-between gap-2 text-xs text-gray-500 dark:text-gray-400">
                                                <span class="inline-flex items-center gap-1.5">
                                                    <span class="font-medium text-gray-700 dark:text-gray-200">
                                                        {{ \App\Models\Activity::typeOptions()[$activity->type] ?? $activity->type }}
                                                    </span>
                                                    <span class="text-gray-300 dark:text-gray-600">·</span>
                                                    <span>{{ $activity->due_at ? \App\Support\Fa::dateTime($activity->due_at, 'Y/m/d') : $dash }}</span>
                                                </span>
                                                <span>{{ $activity->user?->name ?? $dash }}</span>
                                            </div>
                                            <p class="mt-1 text-sm font-medium text-gray-900 dark:text-white">
                                                {{ $activity->subject }}
                                            </p>
                                            @if (filled($activity->body))
                                                <p class="mt-0.5 text-xs leading-5 text-gray-500 dark:text-gray-400">
                                                    {{ $activity->body }}
                                                </p>
                                            @endif
                                        </li>
                                    @endforeach
                                </ul>
                            @endif
                        </div>
                    @else
                        <div class="flex flex-col items-start gap-3 px-4 py-5 sm:flex-row sm:items-center sm:justify-between">
                            <div>
                                <div class="text-xs font-medium text-gray-500">گام جاری</div>
                                <h2 class="mt-0.5 text-base font-semibold text-gray-800 dark:text-gray-100">
                                    گام بعدی تعریف نشده
                                </h2>
                                <p class="mt-1 text-xs text-gray-500">
                                    یک گام مشخص با مهلت بگذارید تا پیگیری قابل‌اندازه‌گیری شود.
                                </p>
                            </div>
                            {{ $this->setStepAction }}
                        </div>
                    @endif
                </section>

                {{-- گام‌های قبلی --}}
                <section class="overflow-hidden rounded-xl border border-gray-200 bg-white shadow-sm dark:border-white/10 dark:bg-gray-900">
                    <div class="flex items-center justify-between border-b border-gray-100 px-4 py-2.5 dark:border-white/10">
                        <h2 class="text-sm font-semibold text-gray-800 dark:text-gray-100">گام‌های قبلی</h2>
                        @if ($closedSteps->isNotEmpty())
                            <span class="text-xs text-gray-400">
                                {{ \App\Support\Fa::digits((string) $closedSteps->count()) }} مورد
                            </span>
                        @endif
                    </div>

                    @forelse ($closedSteps as $step)
                        @php $stepActs = $step->activities->sortByDesc('id'); @endphp
                        <details class="group border-b border-gray-50 last:border-b-0 dark:border-white/5">
                            <summary class="flex cursor-pointer list-none items-center gap-3 px-4 py-3 text-sm transition hover:bg-gray-50 dark:hover:bg-white/5">
                                <div class="min-w-0 flex-1">
                                    <div class="font-medium text-gray-900 dark:text-white">{{ $step->title }}</div>
                                    @if (filled($step->result))
                                        <p class="mt-0.5 truncate text-xs text-gray-500">
                                            {{ \Illuminate\Support\Str::limit($step->result, 90) }}
                                        </p>
                                    @endif
                                </div>
                                <div class="flex shrink-0 items-center gap-2 text-xs text-gray-400">
                                    <span>{{ $step->closed_at ? \App\Support\Fa::dateTime($step->closed_at, 'Y/m/d') : $dash }}</span>
                                    @if ($stepActs->isNotEmpty())
                                        <span class="inline-flex h-5 min-w-5 items-center justify-center rounded-full bg-gray-100 px-1.5 text-[11px] font-medium text-gray-600 dark:bg-white/10 dark:text-gray-300">
                                            {{ \App\Support\Fa::digits((string) $stepActs->count()) }}
                                        </span>
                                    @endif
                                    <x-filament::icon
                                        icon="heroicon-m-chevron-down"
                                        class="h-4 w-4 shrink-0 transition group-open:rotate-180"
                                    />
                                </div>
                            </summary>
                            <div class="space-y-2 border-t border-gray-50 bg-gray-50/60 px-4 py-3 dark:border-white/5 dark:bg-white/[0.02]">
                                @if (filled($step->result))
                                    <p class="text-sm leading-6 text-gray-700 dark:text-gray-300">
                                        <span class="font-medium text-gray-900 dark:text-white">نتیجه:</span>
                                        {{ $step->result }}
                                    </p>
                                @endif
                                @forelse ($stepActs as $activity)
                                    <div class="rounded-lg border border-gray-100 bg-white px-3 py-2 text-sm dark:border-white/10 dark:bg-gray-900/40">
                                        <div class="flex flex-wrap justify-between gap-2 text-xs text-gray-500">
                                            <span>
                                                {{ \App\Models\Activity::typeOptions()[$activity->type] ?? $activity->type }}
                                                ·
                                                {{ $activity->due_at ? \App\Support\Fa::dateTime($activity->due_at, 'Y/m/d') : $dash }}
                                            </span>
                                            <span>{{ $activity->user?->name ?? $dash }}</span>
                                        </div>
                                        <p class="mt-1 font-medium text-gray-900 dark:text-white">{{ $activity->subject }}</p>
                                        @if (filled($activity->body))
                                            <p class="mt-0.5 text-xs text-gray-500">{{ $activity->body }}</p>
                                        @endif
                                    </div>
                                @empty
                                    @if (! filled($step->result))
                                        <p class="text-xs text-gray-400">جزئیاتی ثبت نشده.</p>
                                    @endif
                                @endforelse
                            </div>
                        </details>
                    @empty
                        <div class="px-4 py-8 text-center text-sm text-gray-400">
                            هنوز گام بسته‌شده‌ای نیست.
                        </div>
                    @endforelse
                </section>
            </div>

            {{-- SIDEBAR (در RTL سمت چپ) --}}
            <aside class="flex flex-col gap-3 lg:col-span-4">
                <section class="rounded-xl border border-gray-200 bg-white p-4 shadow-sm dark:border-white/10 dark:bg-gray-900">
                    <div class="text-[11px] font-medium tracking-wide text-gray-400">مخاطب</div>
                    <div class="mt-1 text-base font-bold text-gray-950 dark:text-white">
                        {{ $opportunity->contact_name ?: $dash }}
                    </div>
                    @if ($roleLabel)
                        <div class="mt-0.5 text-xs text-gray-500">{{ $roleLabel }}</div>
                    @endif
                    @if ($opportunity->organization_name)
                        <div class="mt-1.5 text-sm text-gray-600 dark:text-gray-300">
                            {{ $opportunity->organization_name }}
                        </div>
                    @endif

                    <div class="mt-3 space-y-1.5">
                        @if ($opportunity->mobile)
                            <a
                                href="tel:{{ $opportunity->mobile }}"
                                class="flex items-center gap-2 rounded-lg bg-primary-50 px-3 py-2 text-sm font-medium text-primary-700 transition hover:bg-primary-100 dark:bg-primary-400/10 dark:text-primary-300 dark:hover:bg-primary-400/20"
                                dir="ltr"
                            >
                                <x-filament::icon icon="heroicon-m-phone" class="h-4 w-4 shrink-0" />
                                <span class="truncate">{{ \App\Support\Fa::digits($opportunity->mobile) }}</span>
                            </a>
                        @endif
                        @if ($opportunity->phone)
                            <a
                                href="tel:{{ $opportunity->phone }}"
                                class="flex items-center gap-2 rounded-lg bg-gray-50 px-3 py-2 text-sm text-gray-700 transition hover:bg-gray-100 dark:bg-white/5 dark:text-gray-300 dark:hover:bg-white/10"
                                dir="ltr"
                            >
                                <x-filament::icon icon="heroicon-m-phone" class="h-4 w-4 shrink-0" />
                                <span class="truncate">{{ \App\Support\Fa::digits($opportunity->phone) }}</span>
                            </a>
                        @endif
                        @if ($opportunity->email)
                            <a
                                href="mailto:{{ $opportunity->email }}"
                                class="flex items-center gap-2 rounded-lg bg-gray-50 px-3 py-2 text-sm text-gray-700 transition hover:bg-gray-100 dark:bg-white/5 dark:text-gray-300 dark:hover:bg-white/10"
                                dir="ltr"
                            >
                                <x-filament::icon icon="heroicon-m-envelope" class="h-4 w-4 shrink-0" />
                                <span class="truncate">{{ $opportunity->email }}</span>
                            </a>
                        @endif
                    </div>
                </section>

                <section class="overflow-hidden rounded-xl border border-gray-200 bg-white shadow-sm dark:border-white/10 dark:bg-gray-900">
                    <div class="border-b border-gray-100 px-4 py-2.5 text-[11px] font-medium tracking-wide text-gray-400 dark:border-white/10">
                        جزئیات فرصت
                    </div>
                    <dl class="divide-y divide-gray-50 text-sm dark:divide-white/5">
                        <div class="flex items-center justify-between gap-3 px-4 py-2.5">
                            <dt class="shrink-0 text-gray-400">محصول</dt>
                            <dd class="text-left font-medium text-gray-900 dark:text-white">{{ $productLabel ?: $dash }}</dd>
                        </div>
                        <div class="flex items-center justify-between gap-3 px-4 py-2.5">
                            <dt class="shrink-0 text-gray-400">مرحله قیف</dt>
                            <dd class="text-left font-medium text-gray-900 dark:text-white">{{ $stageLabel }}</dd>
                        </div>
                        <div class="flex items-center justify-between gap-3 px-4 py-2.5">
                            <dt class="shrink-0 text-gray-400">وضعیت</dt>
                            <dd class="text-left font-medium text-gray-900 dark:text-white">{{ $statusLabel }}</dd>
                        </div>
                        <div class="flex items-center justify-between gap-3 px-4 py-2.5">
                            <dt class="shrink-0 text-gray-400">مالک</dt>
                            <dd class="text-left font-medium text-gray-900 dark:text-white">{{ $ownerLabel }}</dd>
                        </div>
                        <div class="flex items-center justify-between gap-3 px-4 py-2.5">
                            <dt class="shrink-0 text-gray-400">پیگیری</dt>
                            <dd class="text-left font-medium text-gray-900 dark:text-white">{{ $followupLabel }}</dd>
                        </div>
                    </dl>
                    @if (\App\Filament\Resources\OpportunityResource::canEdit($opportunity))
                        <div class="border-t border-gray-100 px-4 py-2.5 dark:border-white/10">
                            <a
                                href="{{ $editUrl }}"
                                class="text-xs font-medium text-primary-600 hover:text-primary-700 dark:text-primary-400"
                            >
                                ویرایش اطلاعات پایه ←
                            </a>
                        </div>
                    @endif
                </section>
            </aside>
        </div>
    </div>

    <x-filament-actions::modals />
</x-filament-panels::page>
