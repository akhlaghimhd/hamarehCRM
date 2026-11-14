<x-filament-panels::page>
    @php
        $dash = '—';
        $hasOpen = $openStep !== null;
    @endphp

    <div class="grid grid-cols-1 gap-4 xl:grid-cols-12" dir="rtl">
        {{-- سایدبار --}}
        <aside class="order-2 space-y-4 xl:order-1 xl:col-span-4">
            <div class="rounded-2xl border border-gray-200 bg-white p-4 shadow-sm dark:border-white/10 dark:bg-gray-900">
                <div class="mb-2 text-xs font-medium text-primary-600 dark:text-primary-400">مخاطب</div>
                <div class="text-base font-semibold text-gray-950 dark:text-white">
                    {{ $opportunity->contact_name ?: $dash }}
                </div>
                @if ($roleLabel)
                    <div class="mt-0.5 text-xs text-gray-500">{{ $roleLabel }}</div>
                @endif
                @if ($opportunity->organization_name)
                    <div class="mt-1 text-sm text-gray-600 dark:text-gray-300">{{ $opportunity->organization_name }}</div>
                @endif
                <div class="mt-3 space-y-1 text-sm text-gray-600 dark:text-gray-300">
                    @if ($opportunity->mobile)
                        <div dir="ltr">{{ \App\Support\Fa::digits($opportunity->mobile) }}</div>
                    @endif
                    @if ($opportunity->phone)
                        <div dir="ltr">{{ \App\Support\Fa::digits($opportunity->phone) }}</div>
                    @endif
                    @if ($opportunity->email)
                        <div class="truncate">{{ $opportunity->email }}</div>
                    @endif
                </div>
            </div>

            <div class="overflow-hidden rounded-2xl border border-gray-200 bg-white shadow-sm dark:border-white/10 dark:bg-gray-900">
                <div class="border-b border-gray-100 px-4 py-2.5 text-xs font-medium text-primary-600 dark:border-white/10 dark:text-primary-400">
                    جزئیات فرصت
                </div>
                <dl class="divide-y divide-gray-50 text-sm dark:divide-white/5">
                    <div class="flex items-center justify-between gap-3 px-4 py-2.5">
                        <dt class="text-gray-400">محصول</dt>
                        <dd class="font-medium text-gray-950 dark:text-white">{{ $productLabel ?: $dash }}</dd>
                    </div>
                    <div class="flex items-center justify-between gap-3 px-4 py-2.5">
                        <dt class="text-gray-400">مرحله قیف</dt>
                        <dd class="font-medium text-gray-950 dark:text-white">{{ $stageLabel }}</dd>
                    </div>
                    <div class="flex items-center justify-between gap-3 px-4 py-2.5">
                        <dt class="text-gray-400">وضعیت</dt>
                        <dd class="font-medium text-gray-950 dark:text-white">{{ $statusLabel }}</dd>
                    </div>
                    <div class="flex items-center justify-between gap-3 px-4 py-2.5">
                        <dt class="text-gray-400">مالک</dt>
                        <dd class="font-medium text-gray-950 dark:text-white">{{ $ownerLabel }}</dd>
                    </div>
                    <div class="flex items-center justify-between gap-3 px-4 py-2.5">
                        <dt class="text-gray-400">پیگیری</dt>
                        <dd class="font-medium text-gray-950 dark:text-white">{{ $followupLabel }}</dd>
                    </div>
                    @if ($opportunity->updated_at)
                        <div class="flex items-center justify-between gap-3 px-4 py-2.5">
                            <dt class="text-gray-400">آخرین تعامل</dt>
                            <dd class="font-medium text-gray-950 dark:text-white">{{ \App\Support\Fa::dateTime($opportunity->updated_at, 'Y/m/d') }}</dd>
                        </div>
                    @endif
                    @if (filled($opportunity->main_objection) || filled($opportunity->purchase_use))
                        <div class="px-4 py-2.5">
                            <dt class="mb-1 text-gray-400">خلاصه</dt>
                            <dd class="text-gray-700 dark:text-gray-200">{{ $opportunity->main_objection ?: $opportunity->purchase_use }}</dd>
                        </div>
                    @endif
                </dl>
            </div>
        </aside>

        {{-- اصلی --}}
        <section class="order-1 space-y-4 xl:order-2 xl:col-span-8">
            {{-- متا --}}
            <div class="flex flex-wrap items-center gap-2 text-sm">
                <span class="rounded-full bg-gray-100 px-2.5 py-0.5 font-mono text-xs text-gray-700 dark:bg-white/10 dark:text-gray-200">
                    {{ \App\Support\Fa::digits($codeLabel) }}
                </span>
                <span class="rounded-full bg-emerald-50 px-2.5 py-0.5 text-xs text-emerald-800 dark:bg-emerald-900/40 dark:text-emerald-200">
                    {{ $statusLabel }}
                </span>
                <span class="rounded-full bg-amber-50 px-2.5 py-0.5 text-xs text-amber-900 dark:bg-amber-900/40 dark:text-amber-100">
                    {{ $stageLabel }}
                </span>
                @if ($productLabel && $productLabel !== '—')
                    <span class="rounded-full bg-sky-50 px-2.5 py-0.5 text-xs text-sky-900 dark:bg-sky-900/40 dark:text-sky-100">
                        {{ $productLabel }}
                    </span>
                @endif
            </div>

            {{-- گام جاری --}}
            <div class="rounded-2xl border border-emerald-100 bg-emerald-50/70 p-5 shadow-sm dark:border-emerald-900/40 dark:bg-emerald-950/30">
                @if ($hasOpen)
                    <div class="flex flex-col gap-4 sm:flex-row sm:items-start sm:justify-between">
                        {{-- راست: عنوان --}}
                        <div class="min-w-0 flex-1">
                            <div class="mb-1 text-xs font-medium text-emerald-800 dark:text-emerald-200">گام جاری</div>
                            <h2 class="text-lg font-semibold leading-snug text-gray-950 dark:text-white">
                                {{ $openStep->title }}
                            </h2>
                            <div class="mt-2 flex flex-wrap items-center gap-2">
                                @if ($dueLabel)
                                    <span @class([
                                        'text-sm',
                                        'font-semibold text-danger-600 dark:text-danger-400' => $isOverdue,
                                        'text-gray-600 dark:text-gray-300' => ! $isOverdue,
                                    ])>
                                        مهلت {{ \App\Support\Fa::digits($dueLabel) }}
                                        @if ($isOverdue) · عقب‌افتاده @endif
                                    </span>
                                @endif
                                {{ $this->clearStepAction }}
                            </div>
                        </div>

                        {{-- چپ: بستن --}}
                        <div class="shrink-0 sm:max-w-[220px] sm:text-start">
                            {{ $this->closeStepAction }}
                            <p class="mt-2 text-xs leading-5 text-gray-500 dark:text-gray-400">
                                گام را با نتیجه ببندید؛ فعالیت جداگانه از دکمه زیر است.
                            </p>
                        </div>
                    </div>

                    <div class="my-4 border-t border-dashed border-emerald-200/90 dark:border-emerald-800/50"></div>

                    <div>
                        <div class="mb-3 flex flex-wrap items-center justify-between gap-2">
                            <span class="text-sm font-medium text-gray-800 dark:text-gray-100">فعالیت‌های زیر این گام</span>
                            {{ $this->logActivityAction }}
                        </div>

                        @if ($stepActivities->isEmpty())
                            <div class="rounded-xl bg-white/90 px-4 py-5 text-center text-sm text-gray-500 dark:bg-white/5 dark:text-gray-400">
                                فعالیتی ثبت نشده. از «+ ثبت فعالیت» برای تماس/جلسه استفاده کنید.
                            </div>
                        @else
                            <ul class="space-y-2">
                                @foreach ($stepActivities as $activity)
                                    <li class="rounded-xl bg-white px-4 py-3 shadow-sm dark:bg-gray-900/40">
                                        <div class="flex flex-wrap items-center justify-between gap-2 text-xs text-gray-500">
                                            <span>
                                                {{ \App\Models\Activity::typeOptions()[$activity->type] ?? $activity->type }}
                                                ·
                                                {{ $activity->due_at ? \App\Support\Fa::dateTime($activity->due_at, 'Y/m/d') : $dash }}
                                            </span>
                                            <span>{{ $activity->user?->name ?? $dash }}</span>
                                        </div>
                                        <p class="mt-1.5 text-sm font-medium text-gray-950 dark:text-white">{{ $activity->subject }}</p>
                                        @if (filled($activity->body))
                                            <p class="mt-1 text-xs leading-5 text-gray-500">{{ $activity->body }}</p>
                                        @endif
                                    </li>
                                @endforeach
                            </ul>
                        @endif
                    </div>
                @else
                    <div class="flex flex-wrap items-center justify-between gap-3">
                        <div>
                            <div class="mb-1 text-xs font-medium text-emerald-800 dark:text-emerald-200">گام جاری</div>
                            <h2 class="text-base font-semibold text-gray-700 dark:text-gray-200">گام بعدی تعریف نشده</h2>
                        </div>
                        {{ $this->setStepAction }}
                    </div>
                @endif
            </div>

            {{-- گام‌های قبلی --}}
            <div class="overflow-hidden rounded-2xl border border-gray-200 bg-white shadow-sm dark:border-white/10 dark:bg-gray-900">
                <div class="px-5 py-3">
                    <h2 class="text-sm font-semibold text-primary-700 dark:text-primary-300">گام‌های قبلی</h2>
                </div>
                <div class="border-t border-gray-100 dark:border-white/10">
                    @forelse ($closedSteps as $step)
                        @php $stepActs = $step->activities->sortByDesc('id'); @endphp
                        <details class="border-b border-gray-100 last:border-b-0 dark:border-white/5" open>
                            <summary class="flex cursor-pointer list-none flex-wrap items-center justify-between gap-2 bg-emerald-50/50 px-5 py-3 text-sm dark:bg-emerald-950/20">
                                <span class="font-medium text-gray-950 dark:text-white">{{ $step->title }}</span>
                                <span class="flex items-center gap-2 text-xs text-gray-500">
                                    {{ $step->closed_at ? \App\Support\Fa::dateTime($step->closed_at, 'Y/m/d') : $dash }}
                                    · {{ \App\Support\Fa::digits((string) $stepActs->count()) }} فعالیت
                                </span>
                            </summary>
                            <div class="space-y-2.5 px-5 py-4">
                                @if (filled($step->result))
                                    <p class="text-sm leading-6 text-gray-600 dark:text-gray-300">
                                        <span class="font-medium text-gray-800 dark:text-gray-100">نتیجه گام:</span>
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
                                        <p class="mt-1 font-medium text-gray-950 dark:text-white">{{ $activity->subject }}</p>
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
            </div>
        </section>
    </div>

    <x-filament-actions::modals />
</x-filament-panels::page>
