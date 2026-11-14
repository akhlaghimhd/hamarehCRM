<x-filament-panels::page>
    @php
        /** @var \App\Models\Opportunity $opportunity */
        $dash = '—';
    @endphp

    <div class="space-y-5" dir="rtl">
        <nav aria-label="مسیر صفحه" class="text-sm">
            <ol class="flex flex-wrap items-center gap-1.5 text-gray-500 dark:text-gray-400">
                <li>
                    <a href="{{ $listUrl }}" class="font-medium text-gray-600 transition hover:text-primary-600 dark:text-gray-300 dark:hover:text-primary-400">
                        {{ $listLabel }}
                    </a>
                </li>
                <li aria-hidden="true" class="select-none text-gray-300 dark:text-gray-600">/</li>
                <li class="font-medium text-gray-900 dark:text-white" aria-current="page">
                    {{ \Illuminate\Support\Str::limit($pageTitle, 48) }}
                </li>
            </ol>
        </nav>

        <div class="flex flex-wrap items-start justify-between gap-3">
            <div class="min-w-0 flex-1">
                <div class="flex items-center gap-2.5">
                    <span class="inline-flex h-10 w-10 shrink-0 items-center justify-center rounded-xl bg-primary-50 text-primary-600 dark:bg-primary-400/10 dark:text-primary-300">
                        <x-filament::icon icon="heroicon-o-briefcase" class="h-5 w-5" />
                    </span>
                    <div class="min-w-0">
                        <h1 class="truncate text-xl font-bold tracking-tight text-gray-950 dark:text-white">
                            {{ $pageTitle }}
                        </h1>
                        <div class="mt-1.5 flex flex-wrap items-center gap-1.5">
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
                                    'inline-flex items-center rounded-md px-2 py-0.5 text-xs font-medium',
                                    'bg-danger-50 text-danger-700 dark:bg-danger-400/10 dark:text-danger-300' => $isOverdue,
                                    'bg-gray-50 text-gray-600 dark:bg-white/5 dark:text-gray-300' => ! $isOverdue,
                                ])>
                                    مهلت {{ \App\Support\Fa::digits($dueLabel) }}
                                    @if ($isOverdue) · عقب‌افتاده @endif
                                </span>
                            @endif
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="grid grid-cols-1 gap-5 lg:grid-cols-12">
            <div class="space-y-5 lg:col-span-8">
                <section class="overflow-hidden rounded-2xl border border-emerald-100 bg-emerald-50/40 shadow-sm dark:border-emerald-500/20 dark:bg-emerald-500/5">
                    <div class="flex flex-wrap items-start justify-between gap-3 border-b border-emerald-100/80 px-4 py-3 dark:border-emerald-500/15">
                        <div class="min-w-0">
                            <div class="text-xs font-medium text-emerald-800/80 dark:text-emerald-300/80">گام جاری</div>
                            @if (filled($opportunity->next_step))
                                <h2 class="mt-0.5 text-base font-bold text-gray-950 dark:text-white">
                                    {{ $opportunity->next_step }}
                                </h2>
                                @if ($dueLabel)
                                    <p @class([
                                        'mt-1 text-xs',
                                        'font-semibold text-danger-600 dark:text-danger-400' => $isOverdue,
                                        'text-gray-500 dark:text-gray-400' => ! $isOverdue,
                                    ])>
                                        مهلت {{ \App\Support\Fa::digits($dueLabel) }}
                                        @if ($isOverdue) · عقب‌افتاده @endif
                                    </p>
                                @endif
                            @else
                                <h2 class="mt-0.5 text-base font-semibold text-gray-700 dark:text-gray-200">گام بعدی تعریف نشده</h2>
                            @endif
                        </div>
                        <div class="flex flex-wrap items-center gap-2">
                            @if (filled($opportunity->next_step))
                                {{ $this->closeStepAction }}
                                {{ $this->clearStepAction }}
                            @else
                                {{ $this->setStepAction }}
                            @endif
                        </div>
                    </div>

                    @if (filled($opportunity->next_step))
                        <p class="px-4 pt-3 text-xs leading-5 text-gray-500 dark:text-gray-400">
                            گام را با نتیجه ببندید؛ فعالیت جداگانه از دکمهٔ زیر است.
                        </p>
                    @endif

                    <div class="px-4 py-3">
                        <div class="mb-2 flex flex-wrap items-center justify-between gap-2">
                            <span class="text-sm font-semibold text-gray-800 dark:text-gray-100">فعالیت‌های زیر این گام</span>
                            {{ $this->logActivityAction }}
                        </div>

                        @if ($stepActivities->isEmpty())
                            <div class="rounded-xl border border-dashed border-gray-200 bg-white/70 px-3 py-4 text-center text-sm text-gray-500 dark:border-white/10 dark:bg-white/5 dark:text-gray-400">
                                فعالیتی ثبت نشده. از «ثبت فعالیت» برای تماس/جلسه استفاده کنید.
                            </div>
                        @else
                            <ul class="divide-y divide-gray-100 overflow-hidden rounded-xl border border-gray-100 bg-white dark:divide-white/5 dark:border-white/10 dark:bg-gray-900/40">
                                @foreach ($stepActivities as $activity)
                                    <li class="px-3 py-2.5">
                                        <div class="flex flex-wrap items-center justify-between gap-2">
                                            <span class="text-xs font-medium text-gray-500">
                                                {{ \App\Models\Activity::typeOptions()[$activity->type] ?? $activity->type }}
                                                ·
                                                {{ $activity->due_at ? \App\Support\Fa::dateTime($activity->due_at, 'Y/m/d') : $dash }}
                                            </span>
                                            @if ($activity->is_done)
                                                <span class="rounded-full bg-success-50 px-2 py-0.5 text-[11px] font-medium text-success-700 dark:bg-success-400/10 dark:text-success-300">انجام‌شده</span>
                                            @else
                                                <span class="rounded-full bg-amber-50 px-2 py-0.5 text-[11px] font-medium text-amber-800 dark:bg-amber-400/10 dark:text-amber-300">باز</span>
                                            @endif
                                        </div>
                                        <p class="mt-1 text-sm font-medium text-gray-900 dark:text-white">{{ $activity->subject }}</p>
                                        @if (filled($activity->body))
                                            <p class="mt-0.5 text-xs leading-5 text-gray-500 dark:text-gray-400">{{ $activity->body }}</p>
                                        @endif
                                        <p class="mt-1 text-[11px] text-gray-400">{{ $activity->user?->name ?? $dash }}</p>
                                    </li>
                                @endforeach
                            </ul>
                        @endif
                    </div>
                </section>

                <section class="overflow-hidden rounded-2xl border border-gray-200 bg-white shadow-sm dark:border-white/10 dark:bg-gray-900">
                    <header class="border-b border-gray-100 px-4 py-3 dark:border-white/10">
                        <h2 class="text-sm font-semibold text-gray-950 dark:text-white">گام‌های قبلی و ارجاع‌ها</h2>
                    </header>
                    <div class="divide-y divide-gray-100 dark:divide-white/5">
                        @forelse ($closedSteps as $step)
                            <details class="group px-4 py-3">
                                <summary class="flex cursor-pointer list-none flex-wrap items-center justify-between gap-2 text-sm">
                                    <span class="font-medium text-gray-900 dark:text-white">{{ $step->subject }}</span>
                                    <span class="text-xs text-gray-400">
                                        {{ $step->due_at ? \App\Support\Fa::dateTime($step->due_at, 'Y/m/d') : $dash }}
                                        · {{ $step->user?->name ?? $dash }}
                                    </span>
                                </summary>
                                @if (filled($step->body))
                                    <p class="mt-2 whitespace-pre-wrap text-sm leading-6 text-gray-600 dark:text-gray-300">{{ $step->body }}</p>
                                @endif
                            </details>
                        @empty
                            <div class="px-4 py-6 text-center text-sm text-gray-500 dark:text-gray-400">
                                هنوز گام بسته‌شده یا ارجاعی ثبت نشده.
                            </div>
                        @endforelse
                    </div>
                </section>
            </div>

            <aside class="space-y-4 lg:col-span-4">
                <section class="overflow-hidden rounded-2xl border border-gray-200 bg-white shadow-sm dark:border-white/10 dark:bg-gray-900">
                    <header class="border-b border-gray-100 px-4 py-2.5 dark:border-white/10">
                        <h2 class="text-sm font-semibold text-gray-950 dark:text-white">مخاطب</h2>
                    </header>
                    <div class="space-y-2 px-4 py-3 text-sm">
                        <div>
                            <div class="text-xs text-gray-400">نام</div>
                            <div class="font-semibold text-gray-900 dark:text-white">{{ $opportunity->contact_name ?: $dash }}</div>
                        </div>
                        <div>
                            <div class="text-xs text-gray-400">سازمان</div>
                            <div class="font-medium text-gray-800 dark:text-gray-100">{{ $opportunity->organization_name ?: $dash }}</div>
                        </div>
                        <div>
                            <div class="text-xs text-gray-400">نقش</div>
                            <div class="font-medium text-gray-800 dark:text-gray-100">{{ $roleLabel ?: $dash }}</div>
                        </div>
                        @if ($opportunity->mobile)
                            <div>
                                <div class="text-xs text-gray-400">موبایل</div>
                                <a href="tel:{{ $opportunity->mobile }}" class="font-medium text-primary-600 dark:text-primary-400" dir="ltr">
                                    {{ \App\Support\Fa::digits($opportunity->mobile) }}
                                </a>
                            </div>
                        @endif
                        @if ($opportunity->phone)
                            <div>
                                <div class="text-xs text-gray-400">تلفن</div>
                                <a href="tel:{{ $opportunity->phone }}" class="font-medium text-primary-600 dark:text-primary-400" dir="ltr">
                                    {{ \App\Support\Fa::digits($opportunity->phone) }}
                                </a>
                            </div>
                        @endif
                    </div>
                </section>

                <section class="overflow-hidden rounded-2xl border border-gray-200 bg-white shadow-sm dark:border-white/10 dark:bg-gray-900">
                    <header class="border-b border-gray-100 px-4 py-2.5 dark:border-white/10">
                        <h2 class="text-sm font-semibold text-gray-950 dark:text-white">جزئیات فرصت</h2>
                    </header>
                    <dl class="divide-y divide-gray-100 px-4 text-sm dark:divide-white/5">
                        <div class="flex justify-between gap-2 py-2">
                            <dt class="text-gray-400">محصول</dt>
                            <dd class="text-left font-medium text-gray-900 dark:text-white">{{ $productLabel ?: $dash }}</dd>
                        </div>
                        <div class="flex justify-between gap-2 py-2">
                            <dt class="text-gray-400">مرحله</dt>
                            <dd class="text-left font-medium text-gray-900 dark:text-white">{{ $stageLabel }}</dd>
                        </div>
                        <div class="flex justify-between gap-2 py-2">
                            <dt class="text-gray-400">وضعیت</dt>
                            <dd class="text-left font-medium text-gray-900 dark:text-white">{{ $statusLabel }}</dd>
                        </div>
                        <div class="flex justify-between gap-2 py-2">
                            <dt class="text-gray-400">مالک</dt>
                            <dd class="text-left font-medium text-gray-900 dark:text-white">{{ $ownerLabel }}</dd>
                        </div>
                        <div class="flex justify-between gap-2 py-2">
                            <dt class="text-gray-400">پیگیری</dt>
                            <dd class="text-left font-medium text-gray-900 dark:text-white">{{ $followupLabel }}</dd>
                        </div>
                    </dl>
                </section>
            </aside>
        </div>
    </div>

    <x-filament-actions::modals />
</x-filament-panels::page>
