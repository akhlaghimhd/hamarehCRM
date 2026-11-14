<x-filament-panels::page>
    @php
        /** @var \App\Models\Opportunity $opportunity */
        /** @var \App\Models\OpportunityStep|null $openStep */
        $dash = '—';
        $hasOpen = $openStep !== null;
    @endphp

    <div class="fi-opp-workspace space-y-5" dir="rtl">
        {{-- Breadcrumb --}}
        <nav class="text-sm text-gray-500 dark:text-gray-400" aria-label="مسیر">
            <ol class="flex flex-wrap items-center gap-1.5">
                <li><a href="{{ $listUrl }}" class="hover:text-primary-600">{{ $listLabel }}</a></li>
                <li class="text-gray-300" aria-hidden="true">/</li>
                <li class="font-medium text-gray-800 dark:text-gray-100">{{ \Illuminate\Support\Str::limit($pageTitle, 42) }}</li>
            </ol>
        </nav>

        {{-- Title block --}}
        <div>
            <div class="flex items-center gap-2.5">
                <span class="inline-flex h-10 w-10 items-center justify-center rounded-xl bg-primary-50 text-primary-600 dark:bg-primary-400/10 dark:text-primary-300">
                    <x-filament::icon icon="heroicon-o-briefcase" class="h-5 w-5" />
                </span>
                <h1 class="text-xl font-bold text-gray-950 dark:text-white">{{ $pageTitle }}</h1>
            </div>
            <div class="mt-2 flex flex-wrap items-center gap-x-2 gap-y-1 text-sm text-gray-500">
                <span class="font-medium text-gray-700 dark:text-gray-200">{{ \App\Support\Fa::digits($codeLabel) }}</span>
                <span class="text-gray-300">·</span>
                <span>{{ $statusLabel }}</span>
                <span class="text-gray-300">·</span>
                <span>{{ $stageLabel }}</span>
                @if ($productLabel && $productLabel !== '—')
                    <span class="text-gray-300">·</span>
                    <span>{{ $productLabel }}</span>
                @endif
            </div>
        </div>

        {{--
          در RTL: ستون اول سمت راست می‌آید.
          نمونه: کار اصلی راست، سایدبار چپ.
        --}}
        <div class="grid grid-cols-1 gap-5 lg:grid-cols-12">
            {{-- MAIN (راست) --}}
            <div class="flex flex-col gap-5 lg:col-span-8">
                {{-- کارت گام جاری --}}
                <section class="rounded-2xl border border-[#c5e8df] bg-[#e8f7f3] dark:border-teal-500/25 dark:bg-teal-500/10">
                    @if ($hasOpen)
                        <div class="flex flex-col gap-4 p-5 sm:flex-row sm:items-start sm:justify-between">
                            {{-- بلوک عنوان (در RTL سمت راست) --}}
                            <div class="min-w-0 flex-1">
                                <div class="text-xs font-medium text-teal-700 dark:text-teal-300">گام جاری</div>
                                <h2 class="mt-1 text-lg font-bold text-gray-900 dark:text-white">{{ $openStep->title }}</h2>
                                <div class="mt-3 flex flex-wrap items-center gap-2">
                                    @if ($dueLabel)
                                        <span class="text-sm {{ $isOverdue ? 'font-semibold text-danger-600' : 'text-gray-600' }}">
                                            مهلت {{ \App\Support\Fa::digits($dueLabel) }}
                                        </span>
                                    @endif
                                    {{ $this->clearStepAction }}
                                </div>
                            </div>

                            {{-- بلوک بستن (در RTL سمت چپ) --}}
                            <div class="shrink-0 sm:max-w-[220px] sm:text-left">
                                {{ $this->closeStepAction }}
                                <p class="mt-2 text-xs leading-5 text-gray-500 dark:text-gray-400">
                                    گام را با نتیجه ببندید؛ فعالیت جداگانه از دکمهٔ زیر است.
                                </p>
                            </div>
                        </div>

                        <div class="border-t border-dashed border-teal-200/90 dark:border-teal-500/20"></div>

                        <div class="p-5">
                            <div class="mb-3 flex flex-wrap items-center justify-between gap-2">
                                <span class="text-sm font-semibold text-gray-800 dark:text-gray-100">فعالیت‌های زیر این گام</span>
                                {{ $this->logActivityAction }}
                            </div>

                            @if ($stepActivities->isEmpty())
                                <div class="rounded-xl bg-white/90 px-4 py-5 text-center text-sm text-gray-500 dark:bg-white/5 dark:text-gray-400">
                                    فعالیتی ثبت نشده. از «ثبت فعالیت» برای تماس/جلسه استفاده کنید.
                                </div>
                            @else
                                <ul class="space-y-2.5">
                                    @foreach ($stepActivities as $activity)
                                        <li class="rounded-xl bg-white px-4 py-3 shadow-sm dark:bg-gray-900/40">
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
                                            <p class="mt-1.5 text-sm font-medium text-gray-900 dark:text-white">{{ $activity->subject }}</p>
                                            @if (filled($activity->body))
                                                <p class="mt-1 text-xs leading-5 text-gray-500">{{ $activity->body }}</p>
                                            @endif
                                            <p class="mt-1.5 text-[11px] text-gray-400">{{ $activity->user?->name ?? $dash }}</p>
                                        </li>
                                    @endforeach
                                </ul>
                            @endif
                        </div>
                    @else
                        <div class="flex flex-wrap items-center justify-between gap-3 p-5">
                            <div>
                                <div class="text-xs font-medium text-teal-700">گام جاری</div>
                                <h2 class="mt-1 text-base font-semibold text-gray-700 dark:text-gray-200">گام بعدی تعریف نشده</h2>
                            </div>
                            {{ $this->setStepAction }}
                        </div>
                    @endif
                </section>

                {{-- گام‌های قبلی --}}
                <section class="overflow-hidden rounded-2xl border border-gray-200 bg-white dark:border-white/10 dark:bg-gray-900">
                    <div class="px-5 py-3">
                        <h2 class="text-sm font-semibold text-teal-800 dark:text-teal-200">گام‌های قبلی</h2>
                    </div>
                    <div class="border-t border-gray-100 dark:border-white/10">
                        @forelse ($closedSteps as $step)
                            @php $stepActs = $step->activities->sortByDesc('id'); @endphp
                            <details class="border-b border-gray-100 last:border-b-0 dark:border-white/5">
                                <summary class="flex cursor-pointer list-none flex-wrap items-center justify-between gap-2 bg-[#eef9f6] px-5 py-3 text-sm dark:bg-teal-500/5">
                                    <span class="font-medium text-gray-900 dark:text-white">{{ $step->title }}</span>
                                    <span class="text-xs text-gray-500">
                                        {{ $step->closed_at ? \App\Support\Fa::dateTime($step->closed_at, 'Y/m/d') : $dash }}
                                        · {{ \App\Support\Fa::digits((string) $stepActs->count()) }} فعالیت
                                    </span>
                                </summary>
                                <div class="space-y-2.5 px-5 py-4">
                                    @if (filled($step->result))
                                        <p class="text-sm leading-6 text-gray-600">
                                            <span class="font-medium text-gray-800">نتیجه گام:</span>
                                            {{ $step->result }}
                                        </p>
                                    @endif
                                    @foreach ($stepActs as $activity)
                                        <div class="rounded-lg border border-gray-100 px-3 py-2.5 text-sm dark:border-white/10">
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
                                    @endforeach
                                </div>
                            </details>
                        @empty
                            <div class="px-5 py-6 text-center text-sm text-gray-400">هنوز گام بسته‌شده‌ای نیست.</div>
                        @endforelse
                    </div>
                </section>
            </div>

            {{-- SIDEBAR (چپ) --}}
            <aside class="flex flex-col gap-4 lg:col-span-4">
                <section class="rounded-2xl border border-gray-200 bg-white p-4 dark:border-white/10 dark:bg-gray-900">
                    <div class="text-xs font-medium text-teal-700 dark:text-teal-300">مخاطب</div>
                    <div class="mt-1.5 text-base font-bold text-gray-950 dark:text-white">
                        {{ $opportunity->contact_name ?: $dash }}
                    </div>
                    @if ($roleLabel)
                        <div class="mt-0.5 text-xs text-gray-500">{{ $roleLabel }}</div>
                    @endif
                    <div class="mt-3 space-y-1.5 text-sm">
                        @if ($opportunity->organization_name)
                            <div class="text-gray-600 dark:text-gray-300">{{ $opportunity->organization_name }}</div>
                        @endif
                        @if ($opportunity->mobile)
                            <a href="tel:{{ $opportunity->mobile }}" class="block text-primary-600" dir="ltr">{{ \App\Support\Fa::digits($opportunity->mobile) }}</a>
                        @endif
                        @if ($opportunity->phone)
                            <a href="tel:{{ $opportunity->phone }}" class="block text-primary-600" dir="ltr">{{ \App\Support\Fa::digits($opportunity->phone) }}</a>
                        @endif
                    </div>
                </section>

                <section class="overflow-hidden rounded-2xl border border-gray-200 bg-white dark:border-white/10 dark:bg-gray-900">
                    <div class="border-b border-gray-100 px-4 py-2.5 text-xs font-medium text-teal-700 dark:border-white/10 dark:text-teal-300">
                        جزئیات فرصت
                    </div>
                    <dl class="text-sm">
                        <div class="flex items-center justify-between gap-3 border-b border-gray-50 px-4 py-2.5 dark:border-white/5">
                            <dt class="text-gray-400">محصول</dt>
                            <dd class="font-medium text-gray-900 dark:text-white">{{ $productLabel ?: $dash }}</dd>
                        </div>
                        <div class="flex items-center justify-between gap-3 border-b border-gray-50 px-4 py-2.5 dark:border-white/5">
                            <dt class="text-gray-400">مرحله قیف</dt>
                            <dd class="font-medium text-gray-900 dark:text-white">{{ $stageLabel }}</dd>
                        </div>
                        <div class="flex items-center justify-between gap-3 border-b border-gray-50 px-4 py-2.5 dark:border-white/5">
                            <dt class="text-gray-400">وضعیت</dt>
                            <dd class="font-medium text-gray-900 dark:text-white">{{ $statusLabel }}</dd>
                        </div>
                        <div class="flex items-center justify-between gap-3 border-b border-gray-50 px-4 py-2.5 dark:border-white/5">
                            <dt class="text-gray-400">مالک</dt>
                            <dd class="font-medium text-gray-900 dark:text-white">{{ $ownerLabel }}</dd>
                        </div>
                        <div class="flex items-center justify-between gap-3 px-4 py-2.5">
                            <dt class="text-gray-400">پیگیری</dt>
                            <dd class="font-medium text-gray-900 dark:text-white">{{ $followupLabel }}</dd>
                        </div>
                    </dl>
                </section>
            </aside>
        </div>
    </div>

    <x-filament-actions::modals />
</x-filament-panels::page>
