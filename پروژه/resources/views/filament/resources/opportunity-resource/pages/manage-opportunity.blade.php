<x-filament-panels::page>
    @php
        /** @var \App\Models\Opportunity $opportunity */
        /** @var \App\Models\OpportunityStep|null $openStep */
        $dash = '—';
        $hasOpen = $openStep !== null;
    @endphp

    <div class="space-y-6" dir="rtl">
        {{-- مسیر --}}
        <nav aria-label="مسیر صفحه" class="text-sm text-gray-500 dark:text-gray-400">
            <ol class="flex flex-wrap items-center gap-2">
                <li>
                    <a href="{{ $listUrl }}" class="hover:text-primary-600 dark:hover:text-primary-400">{{ $listLabel }}</a>
                </li>
                <li aria-hidden="true" class="text-gray-300 dark:text-gray-600">/</li>
                <li class="font-medium text-gray-800 dark:text-gray-100" aria-current="page">
                    {{ \Illuminate\Support\Str::limit($pageTitle, 40) }}
                </li>
            </ol>
        </nav>

        {{-- عنوان + متا --}}
        <header class="space-y-3">
            <div class="flex items-center gap-3">
                <span class="inline-flex h-11 w-11 shrink-0 items-center justify-center rounded-2xl bg-primary-50 text-primary-600 dark:bg-primary-400/10 dark:text-primary-300">
                    <x-filament::icon icon="heroicon-o-briefcase" class="h-5 w-5" />
                </span>
                <h1 class="text-xl font-bold leading-snug text-gray-950 dark:text-white sm:text-2xl">
                    {{ $pageTitle }}
                </h1>
            </div>
            <p class="flex flex-wrap items-center gap-x-2.5 gap-y-1.5 text-sm text-gray-500 dark:text-gray-400">
                <span class="font-medium text-gray-700 dark:text-gray-200">{{ \App\Support\Fa::digits($codeLabel) }}</span>
                <span class="text-gray-300">·</span>
                <span>{{ $statusLabel }}</span>
                <span class="text-gray-300">·</span>
                <span>{{ $stageLabel }}</span>
                @if ($productLabel && $productLabel !== '—')
                    <span class="text-gray-300">·</span>
                    <span>{{ $productLabel }}</span>
                @endif
                @if ($dueLabel)
                    <span class="text-gray-300">·</span>
                    <span @class(['font-medium text-danger-600 dark:text-danger-400' => $isOverdue])>
                        مهلت {{ \App\Support\Fa::digits($dueLabel) }}
                        @if ($isOverdue) (عقب‌افتاده) @endif
                    </span>
                @endif
            </p>
        </header>

        <div class="grid grid-cols-1 items-start gap-6 lg:grid-cols-12">
            {{-- ستون کار --}}
            <div class="space-y-6 lg:col-span-8">
                {{-- گام جاری --}}
                <section class="overflow-hidden rounded-2xl border border-teal-100 bg-teal-50/70 dark:border-teal-500/20 dark:bg-teal-500/10">
                    <div class="flex flex-wrap items-start justify-between gap-4 px-5 pt-5">
                        <div class="min-w-0 flex-1 space-y-2">
                            <div class="text-xs font-medium text-teal-700 dark:text-teal-300">گام جاری</div>
                            @if ($hasOpen)
                                <h2 class="text-lg font-bold text-gray-900 dark:text-white">{{ $openStep->title }}</h2>
                                <div class="flex flex-wrap items-center gap-3">
                                    @if ($dueLabel)
                                        <span @class([
                                            'text-sm',
                                            'font-semibold text-danger-600' => $isOverdue,
                                            'text-gray-500' => ! $isOverdue,
                                        ])>
                                            مهلت {{ \App\Support\Fa::digits($dueLabel) }}
                                        </span>
                                    @endif
                                    {{ $this->clearStepAction }}
                                </div>
                            @else
                                <h2 class="text-base font-semibold text-gray-600 dark:text-gray-300">گام بعدی تعریف نشده</h2>
                            @endif
                        </div>
                        <div class="flex flex-col items-stretch gap-2 sm:items-end">
                            @if ($hasOpen)
                                {{ $this->closeStepAction }}
                                <p class="max-w-[15rem] text-xs leading-6 text-gray-500 dark:text-gray-400">
                                    گام را با نتیجه ببندید؛ فعالیت جداگانه از دکمهٔ زیر است.
                                </p>
                            @else
                                {{ $this->setStepAction }}
                            @endif
                        </div>
                    </div>

                    <div class="mx-5 mt-5 border-t border-dashed border-teal-200/80 dark:border-teal-500/20"></div>

                    <div class="space-y-3 px-5 py-5">
                        <div class="flex flex-wrap items-center justify-between gap-3">
                            <span class="text-sm font-semibold text-teal-900 dark:text-teal-100">فعالیت‌های زیر این گام</span>
                            @if ($hasOpen)
                                {{ $this->logActivityAction }}
                            @endif
                        </div>

                        @if (! $hasOpen)
                            <div class="rounded-xl bg-white/90 px-4 py-6 text-center text-sm leading-6 text-gray-500 dark:bg-white/5 dark:text-gray-400">
                                ابتدا یک گام تعریف کنید تا بتوانید فعالیت ثبت کنید.
                            </div>
                        @elseif ($stepActivities->isEmpty())
                            <div class="rounded-xl bg-white/90 px-4 py-6 text-center text-sm leading-6 text-gray-500 dark:bg-white/5 dark:text-gray-400">
                                فعالیتی ثبت نشده. از «ثبت فعالیت» برای تماس/جلسه استفاده کنید.
                            </div>
                        @else
                            <ul class="space-y-3">
                                @foreach ($stepActivities as $activity)
                                    <li class="rounded-xl bg-white px-4 py-3.5 shadow-sm dark:bg-gray-900/50">
                                        <div class="flex flex-wrap items-center justify-between gap-2 text-xs text-gray-500">
                                            <span>
                                                {{ \App\Models\Activity::typeOptions()[$activity->type] ?? $activity->type }}
                                                ·
                                                {{ $activity->due_at ? \App\Support\Fa::dateTime($activity->due_at, 'Y/m/d') : $dash }}
                                            </span>
                                            @if ($activity->is_done)
                                                <span class="rounded-full bg-emerald-50 px-2 py-0.5 text-[11px] font-medium text-emerald-700">انجام‌شده</span>
                                            @else
                                                <span class="rounded-full bg-amber-50 px-2 py-0.5 text-[11px] font-medium text-amber-800">باز</span>
                                            @endif
                                        </div>
                                        <p class="mt-2 text-sm font-medium text-gray-900 dark:text-white">{{ $activity->subject }}</p>
                                        @if (filled($activity->body))
                                            <p class="mt-1 text-xs leading-5 text-gray-500">{{ $activity->body }}</p>
                                        @endif
                                        <p class="mt-2 text-[11px] text-gray-400">{{ $activity->user?->name ?? $dash }}</p>
                                    </li>
                                @endforeach
                            </ul>
                        @endif
                    </div>
                </section>

                {{-- گام‌های قبلی --}}
                <section class="overflow-hidden rounded-2xl border border-gray-200 bg-white dark:border-white/10 dark:bg-gray-900">
                    <div class="border-b border-gray-100 px-5 py-3.5 dark:border-white/10">
                        <h2 class="text-sm font-semibold text-teal-800 dark:text-teal-200">گام‌های قبلی</h2>
                    </div>
                    <div class="divide-y divide-gray-100 dark:divide-white/5">
                        @forelse ($closedSteps as $step)
                            @php $stepActs = $step->activities->sortByDesc('id'); @endphp
                            <details class="group">
                                <summary class="flex cursor-pointer list-none flex-wrap items-center justify-between gap-3 bg-teal-50/40 px-5 py-3.5 text-sm hover:bg-teal-50/70 dark:bg-teal-500/5 dark:hover:bg-teal-500/10">
                                    <span class="font-medium text-gray-900 dark:text-white">{{ $step->title }}</span>
                                    <span class="text-xs text-gray-500">
                                        {{ $step->closed_at ? \App\Support\Fa::dateTime($step->closed_at, 'Y/m/d') : $dash }}
                                        · {{ \App\Support\Fa::digits((string) $stepActs->count()) }} فعالیت
                                    </span>
                                </summary>
                                <div class="space-y-3 px-5 py-4">
                                    @if (filled($step->result))
                                        <p class="text-sm leading-6 text-gray-600 dark:text-gray-300">
                                            <span class="font-medium text-gray-800 dark:text-gray-100">نتیجه گام:</span>
                                            {{ $step->result }}
                                        </p>
                                    @endif
                                    @foreach ($stepActs as $activity)
                                        <div class="rounded-xl border border-gray-100 px-4 py-3 text-sm dark:border-white/10">
                                            <div class="flex flex-wrap justify-between gap-2 text-xs text-gray-500">
                                                <span>
                                                    {{ \App\Models\Activity::typeOptions()[$activity->type] ?? $activity->type }}
                                                    ·
                                                    {{ $activity->due_at ? \App\Support\Fa::dateTime($activity->due_at, 'Y/m/d') : $dash }}
                                                </span>
                                                <span>{{ $activity->user?->name ?? $dash }}</span>
                                            </div>
                                            <p class="mt-2 font-medium text-gray-900 dark:text-white">{{ $activity->subject }}</p>
                                            @if (filled($activity->body))
                                                <p class="mt-1 text-xs leading-5 text-gray-500">{{ $activity->body }}</p>
                                            @endif
                                        </div>
                                    @endforeach
                                </div>
                            </details>
                        @empty
                            <div class="px-5 py-8 text-center text-sm text-gray-400">هنوز گام بسته‌شده‌ای نیست.</div>
                        @endforelse
                    </div>
                </section>
            </div>

            {{-- سایدبار --}}
            <aside class="space-y-5 lg:col-span-4">
                <section class="rounded-2xl border border-gray-200 bg-white px-5 py-4 dark:border-white/10 dark:bg-gray-900">
                    <div class="text-xs font-medium text-teal-700 dark:text-teal-300">مخاطب</div>
                    <div class="mt-2 text-lg font-bold text-gray-950 dark:text-white">
                        {{ $opportunity->contact_name ?: $dash }}
                    </div>
                    @if ($roleLabel)
                        <div class="mt-1 text-xs text-gray-500">{{ $roleLabel }}</div>
                    @endif
                    <div class="mt-4 space-y-2 text-sm">
                        @if ($opportunity->organization_name)
                            <div class="text-gray-700 dark:text-gray-200">{{ $opportunity->organization_name }}</div>
                        @endif
                        @if ($opportunity->mobile)
                            <a href="tel:{{ $opportunity->mobile }}" class="block text-primary-600 dark:text-primary-400" dir="ltr">
                                {{ \App\Support\Fa::digits($opportunity->mobile) }}
                            </a>
                        @endif
                        @if ($opportunity->phone)
                            <a href="tel:{{ $opportunity->phone }}" class="block text-primary-600 dark:text-primary-400" dir="ltr">
                                {{ \App\Support\Fa::digits($opportunity->phone) }}
                            </a>
                        @endif
                    </div>
                </section>

                <section class="rounded-2xl border border-gray-200 bg-white dark:border-white/10 dark:bg-gray-900">
                    <div class="border-b border-gray-100 px-5 py-3.5 text-xs font-medium text-teal-700 dark:border-white/10 dark:text-teal-300">
                        جزئیات فرصت
                    </div>
                    <dl class="px-5 text-sm">
                        <div class="flex items-center justify-between gap-3 border-b border-gray-50 py-3.5 dark:border-white/5">
                            <dt class="text-gray-400">محصول</dt>
                            <dd class="text-left font-medium text-gray-900 dark:text-white">{{ $productLabel ?: $dash }}</dd>
                        </div>
                        <div class="flex items-center justify-between gap-3 border-b border-gray-50 py-3.5 dark:border-white/5">
                            <dt class="text-gray-400">مرحله قیف</dt>
                            <dd class="text-left font-medium text-gray-900 dark:text-white">{{ $stageLabel }}</dd>
                        </div>
                        <div class="flex items-center justify-between gap-3 border-b border-gray-50 py-3.5 dark:border-white/5">
                            <dt class="text-gray-400">وضعیت</dt>
                            <dd class="text-left font-medium text-gray-900 dark:text-white">{{ $statusLabel }}</dd>
                        </div>
                        <div class="flex items-center justify-between gap-3 border-b border-gray-50 py-3.5 dark:border-white/5">
                            <dt class="text-gray-400">مالک</dt>
                            <dd class="text-left font-medium text-gray-900 dark:text-white">{{ $ownerLabel }}</dd>
                        </div>
                        <div class="flex items-center justify-between gap-3 py-3.5">
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
