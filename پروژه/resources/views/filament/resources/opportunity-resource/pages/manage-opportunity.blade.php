<x-filament-panels::page>
    @php
        /** @var \App\Models\Opportunity $opportunity */
        $dash = '—';
    @endphp

    <div class="space-y-4" dir="rtl">
        {{-- عنوان + متا فشرده (breadcrumb از Filament می‌آید) --}}
        <div class="flex items-start gap-3">
            <span class="mt-0.5 inline-flex h-11 w-11 shrink-0 items-center justify-center rounded-2xl bg-primary-50 text-primary-600 ring-1 ring-primary-100 dark:bg-primary-400/10 dark:text-primary-300 dark:ring-primary-400/20">
                <x-filament::icon icon="heroicon-o-briefcase" class="h-5 w-5" />
            </span>
            <div class="min-w-0 flex-1">
                <h1 class="text-lg font-bold leading-7 text-gray-950 dark:text-white sm:text-xl">
                    {{ $pageTitle }}
                </h1>
                <div class="mt-2 flex flex-wrap items-center gap-1.5">
                    <span class="rounded-full bg-gray-100 px-2.5 py-0.5 text-[11px] font-semibold tracking-wide text-gray-700 dark:bg-white/10 dark:text-gray-200">
                        {{ \App\Support\Fa::digits($codeLabel) }}
                    </span>
                    <span class="rounded-full bg-emerald-50 px-2.5 py-0.5 text-[11px] font-medium text-emerald-800 dark:bg-emerald-400/10 dark:text-emerald-300">
                        {{ $statusLabel }}
                    </span>
                    <span class="rounded-full bg-amber-50 px-2.5 py-0.5 text-[11px] font-medium text-amber-900 dark:bg-amber-400/10 dark:text-amber-300">
                        {{ $stageLabel }}
                    </span>
                    @if ($productLabel && $productLabel !== '—')
                        <span class="rounded-full bg-slate-50 px-2.5 py-0.5 text-[11px] text-slate-600 dark:bg-white/5 dark:text-slate-300">
                            {{ $productLabel }}
                        </span>
                    @endif
                    @if ($dueLabel)
                        <span @class([
                            'rounded-full px-2.5 py-0.5 text-[11px] font-medium',
                            'bg-rose-50 text-rose-700 dark:bg-rose-400/10 dark:text-rose-300' => $isOverdue,
                            'bg-slate-50 text-slate-600 dark:bg-white/5 dark:text-slate-300' => ! $isOverdue,
                        ])>
                            مهلت {{ \App\Support\Fa::digits($dueLabel) }}
                            @if ($isOverdue) · عقب‌افتاده @endif
                        </span>
                    @endif
                </div>
            </div>
        </div>

        <div class="grid grid-cols-1 gap-4 lg:grid-cols-12 lg:gap-5">
            {{-- کار اصلی --}}
            <div class="space-y-4 lg:col-span-8">
                <section class="overflow-hidden rounded-2xl border border-teal-200/80 bg-gradient-to-b from-teal-50/80 to-white shadow-sm dark:border-teal-500/20 dark:from-teal-500/10 dark:to-gray-900">
                    <div class="flex flex-wrap items-start justify-between gap-3 px-4 py-3.5">
                        <div class="min-w-0 max-w-xl">
                            <div class="text-[11px] font-semibold uppercase tracking-wide text-teal-700/80 dark:text-teal-300/80">گام جاری</div>
                            @if (filled($opportunity->next_step))
                                <h2 class="mt-1 text-base font-bold text-gray-950 dark:text-white">
                                    {{ $opportunity->next_step }}
                                </h2>
                                @if ($dueLabel)
                                    <p @class([
                                        'mt-1 text-xs',
                                        'font-semibold text-rose-600 dark:text-rose-400' => $isOverdue,
                                        'text-gray-500 dark:text-gray-400' => ! $isOverdue,
                                    ])>
                                        مهلت {{ \App\Support\Fa::digits($dueLabel) }}
                                        @if ($isOverdue) · عقب‌افتاده @endif
                                    </p>
                                @endif
                            @else
                                <h2 class="mt-1 text-base font-semibold text-gray-600 dark:text-gray-300">گام بعدی تعریف نشده</h2>
                                <p class="mt-1 text-xs text-gray-500">با «تعریف گام» کار بعدی را مشخص کنید.</p>
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

                    <div class="border-t border-teal-100/80 px-4 py-3 dark:border-teal-500/15">
                        <div class="mb-2.5 flex flex-wrap items-center justify-between gap-2">
                            <span class="text-sm font-semibold text-gray-800 dark:text-gray-100">فعالیت‌های زیر این گام</span>
                            {{ $this->logActivityAction }}
                        </div>

                        @if ($stepActivities->isEmpty())
                            <div class="rounded-xl border border-dashed border-gray-200 bg-white/90 px-3 py-5 text-center text-sm text-gray-500 dark:border-white/10 dark:bg-white/5 dark:text-gray-400">
                                هنوز فعالیتی نیست — تماس یا جلسه را اینجا ثبت کنید.
                            </div>
                        @else
                            <ul class="space-y-2">
                                @foreach ($stepActivities as $activity)
                                    <li class="rounded-xl border border-gray-100 bg-white px-3 py-2.5 shadow-sm dark:border-white/10 dark:bg-gray-900/60">
                                        <div class="flex flex-wrap items-center justify-between gap-2">
                                            <div class="flex flex-wrap items-center gap-2 text-xs text-gray-500">
                                                <span class="rounded-md bg-gray-50 px-1.5 py-0.5 font-medium text-gray-700 dark:bg-white/10 dark:text-gray-200">
                                                    {{ \App\Models\Activity::typeOptions()[$activity->type] ?? $activity->type }}
                                                </span>
                                                <span>{{ $activity->due_at ? \App\Support\Fa::dateTime($activity->due_at, 'Y/m/d') : $dash }}</span>
                                            </div>
                                            @if ($activity->is_done)
                                                <span class="rounded-full bg-emerald-50 px-2 py-0.5 text-[11px] font-medium text-emerald-700 dark:bg-emerald-400/10 dark:text-emerald-300">انجام‌شده</span>
                                            @else
                                                <span class="rounded-full bg-amber-50 px-2 py-0.5 text-[11px] font-medium text-amber-800 dark:bg-amber-400/10 dark:text-amber-300">باز</span>
                                            @endif
                                        </div>
                                        <p class="mt-1.5 text-sm font-medium text-gray-900 dark:text-white">{{ $activity->subject }}</p>
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

                <section class="overflow-hidden rounded-2xl border border-gray-200/90 bg-white shadow-sm dark:border-white/10 dark:bg-gray-900">
                    <header class="flex items-center justify-between border-b border-gray-100 px-4 py-2.5 dark:border-white/10">
                        <h2 class="text-sm font-semibold text-gray-900 dark:text-white">گام‌های قبلی و ارجاع‌ها</h2>
                        <span class="text-xs text-gray-400">{{ \App\Support\Fa::digits((string) $closedSteps->count()) }} مورد</span>
                    </header>
                    <div class="divide-y divide-gray-50 dark:divide-white/5">
                        @forelse ($closedSteps as $step)
                            <details class="group px-4 py-2.5 open:bg-gray-50/50 dark:open:bg-white/5">
                                <summary class="flex cursor-pointer list-none flex-wrap items-center justify-between gap-2 text-sm marker:content-none">
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
                            <div class="px-4 py-5 text-center text-sm text-gray-400">
                                هنوز چیزی بسته یا ارجاع نشده.
                            </div>
                        @endforelse
                    </div>
                </section>
            </div>

            {{-- سایدبار --}}
            <aside class="space-y-3 lg:col-span-4">
                <section class="overflow-hidden rounded-2xl border border-gray-200/90 bg-white shadow-sm dark:border-white/10 dark:bg-gray-900">
                    <div class="border-b border-gray-100 px-4 py-2.5 dark:border-white/10">
                        <h2 class="text-xs font-semibold text-gray-500 dark:text-gray-400">مخاطب</h2>
                        <p class="mt-0.5 text-base font-bold text-gray-950 dark:text-white">
                            {{ $opportunity->contact_name ?: $dash }}
                        </p>
                        @if ($roleLabel)
                            <p class="text-xs text-gray-500">{{ $roleLabel }}</p>
                        @endif
                    </div>
                    <dl class="space-y-2.5 px-4 py-3 text-sm">
                        <div>
                            <dt class="text-[11px] text-gray-400">سازمان</dt>
                            <dd class="font-medium text-gray-900 dark:text-white">{{ $opportunity->organization_name ?: $dash }}</dd>
                        </div>
                        @if ($opportunity->mobile)
                            <div>
                                <dt class="text-[11px] text-gray-400">موبایل</dt>
                                <dd>
                                    <a href="tel:{{ $opportunity->mobile }}" class="font-medium text-primary-600 dark:text-primary-400" dir="ltr">
                                        {{ \App\Support\Fa::digits($opportunity->mobile) }}
                                    </a>
                                </dd>
                            </div>
                        @endif
                        @if ($opportunity->phone)
                            <div>
                                <dt class="text-[11px] text-gray-400">تلفن</dt>
                                <dd>
                                    <a href="tel:{{ $opportunity->phone }}" class="font-medium text-primary-600 dark:text-primary-400" dir="ltr">
                                        {{ \App\Support\Fa::digits($opportunity->phone) }}
                                    </a>
                                </dd>
                            </div>
                        @endif
                        @if ($opportunity->email)
                            <div>
                                <dt class="text-[11px] text-gray-400">ایمیل</dt>
                                <dd class="truncate">
                                    <a href="mailto:{{ $opportunity->email }}" class="font-medium text-primary-600 dark:text-primary-400">
                                        {{ $opportunity->email }}
                                    </a>
                                </dd>
                            </div>
                        @endif
                    </dl>
                </section>

                <section class="overflow-hidden rounded-2xl border border-gray-200/90 bg-white shadow-sm dark:border-white/10 dark:bg-gray-900">
                    <div class="border-b border-gray-100 px-4 py-2.5 dark:border-white/10">
                        <h2 class="text-xs font-semibold text-gray-500 dark:text-gray-400">جزئیات فرصت</h2>
                    </div>
                    <dl class="divide-y divide-gray-50 px-4 text-sm dark:divide-white/5">
                        <div class="flex items-center justify-between gap-2 py-2">
                            <dt class="text-gray-400">محصول</dt>
                            <dd class="text-left font-medium text-gray-900 dark:text-white">{{ $productLabel ?: $dash }}</dd>
                        </div>
                        <div class="flex items-center justify-between gap-2 py-2">
                            <dt class="text-gray-400">مرحله</dt>
                            <dd class="text-left font-medium text-gray-900 dark:text-white">{{ $stageLabel }}</dd>
                        </div>
                        <div class="flex items-center justify-between gap-2 py-2">
                            <dt class="text-gray-400">وضعیت</dt>
                            <dd class="text-left font-medium text-gray-900 dark:text-white">{{ $statusLabel }}</dd>
                        </div>
                        <div class="flex items-center justify-between gap-2 py-2">
                            <dt class="text-gray-400">مالک</dt>
                            <dd class="text-left font-medium text-gray-900 dark:text-white">{{ $ownerLabel }}</dd>
                        </div>
                        <div class="flex items-center justify-between gap-2 py-2">
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
