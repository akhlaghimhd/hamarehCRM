<x-filament-panels::page>
    @php
        /** @var \App\Models\Opportunity $opportunity */
        $opportunity = $opportunity;
    @endphp

    <div class="grid grid-cols-1 gap-4 xl:grid-cols-12" dir="rtl">
        {{-- سایدبار خلاصه --}}
        <aside class="xl:col-span-4 space-y-4 order-2 xl:order-1">
            <div class="rounded-2xl border border-gray-200 bg-white p-4 shadow-sm dark:border-gray-700 dark:bg-gray-900">
                <div class="text-xs text-gray-500 mb-2">مخاطب</div>
                <div class="text-base font-semibold text-gray-900 dark:text-white">
                    {{ $opportunity->contact_name ?: '—' }}
                </div>
                @if ($opportunity->mobile)
                    <div class="mt-2 text-sm text-gray-600 dark:text-gray-300" dir="ltr">{{ \App\Support\Fa::digits($opportunity->mobile) }}</div>
                @endif
                @if ($opportunity->phone)
                    <div class="text-sm text-gray-600 dark:text-gray-300" dir="ltr">{{ \App\Support\Fa::digits($opportunity->phone) }}</div>
                @endif
                @if ($opportunity->email)
                    <div class="mt-1 text-sm text-gray-500 truncate">{{ $opportunity->email }}</div>
                @endif
                @if ($opportunity->organization_name)
                    <div class="mt-3 pt-3 border-t border-gray-100 dark:border-gray-800 text-sm text-gray-600 dark:text-gray-300">
                        {{ $opportunity->organization_name }}
                    </div>
                @endif
            </div>

            <div class="rounded-2xl border border-gray-200 bg-white p-4 shadow-sm dark:border-gray-700 dark:bg-gray-900">
                <div class="text-xs text-gray-500 mb-3">جزئیات فرصت</div>
                <dl class="space-y-2 text-sm">
                    <div class="flex justify-between gap-3">
                        <dt class="text-gray-500">محصول</dt>
                        <dd class="font-medium text-gray-900 dark:text-white text-left">{{ $productLabel }}</dd>
                    </div>
                    <div class="flex justify-between gap-3">
                        <dt class="text-gray-500">مرحله قیف</dt>
                        <dd class="font-medium text-gray-900 dark:text-white">{{ $stageLabel }}</dd>
                    </div>
                    <div class="flex justify-between gap-3">
                        <dt class="text-gray-500">وضعیت</dt>
                        <dd class="font-medium text-gray-900 dark:text-white">{{ $statusLabel }}</dd>
                    </div>
                    <div class="flex justify-between gap-3">
                        <dt class="text-gray-500">مالک</dt>
                        <dd class="font-medium text-gray-900 dark:text-white">{{ $ownerLabel }}</dd>
                    </div>
                    @if ($opportunity->amount)
                        <div class="flex justify-between gap-3">
                            <dt class="text-gray-500">بودجه</dt>
                            <dd class="font-medium text-gray-900 dark:text-white" dir="ltr">{{ \App\Support\Fa::number($opportunity->amount) }}</dd>
                        </div>
                    @endif
                </dl>
            </div>
        </aside>

        <section class="xl:col-span-8 space-y-4 order-1 xl:order-2">
            <div class="flex flex-wrap items-center gap-2 text-sm text-gray-600 dark:text-gray-300">
                <span class="rounded-full bg-gray-100 px-2.5 py-0.5 font-mono text-xs dark:bg-gray-800">{{ $codeLabel }}</span>
                <span class="rounded-full bg-emerald-50 text-emerald-800 px-2.5 py-0.5 text-xs dark:bg-emerald-900/40 dark:text-emerald-200">{{ $statusLabel }}</span>
                <span class="rounded-full bg-amber-50 text-amber-900 px-2.5 py-0.5 text-xs dark:bg-amber-900/40 dark:text-amber-100">{{ $stageLabel }}</span>
                @if ($productLabel && $productLabel !== '—')
                    <span class="rounded-full bg-sky-50 text-sky-900 px-2.5 py-0.5 text-xs dark:bg-sky-900/40 dark:text-sky-100">{{ $productLabel }}</span>
                @endif
            </div>

            <div class="rounded-2xl border border-emerald-100 bg-emerald-50/60 p-5 shadow-sm dark:border-emerald-900/40 dark:bg-emerald-950/30">
                <div class="flex flex-wrap items-start justify-between gap-3">
                    <div>
                        <div class="text-xs font-medium text-emerald-800 dark:text-emerald-200 mb-1">گام جاری</div>
                        @if (filled($opportunity->next_step))
                            <h2 class="text-lg font-semibold text-gray-900 dark:text-white leading-snug">
                                {{ $opportunity->next_step }}
                            </h2>
                            <div class="mt-2 flex flex-wrap items-center gap-2 text-sm">
                                @if ($dueLabel)
                                    <span class="text-gray-600 dark:text-gray-300">مهلت {{ $dueLabel }}</span>
                                @endif
                                @if ($isOverdue)
                                    <span class="rounded-full bg-rose-100 text-rose-800 px-2 py-0.5 text-xs dark:bg-rose-900/50 dark:text-rose-200">عقب‌افتاده</span>
                                @endif
                            </div>
                        @else
                            <p class="text-sm text-gray-600 dark:text-gray-300">گامی تعریف نشده. برای ادامه پیگیری یک گام بسازید.</p>
                        @endif
                    </div>

                    <div class="flex flex-wrap gap-2">
                        @if (filled($opportunity->next_step))
                            {{ $this->closeStepAction }}
                            {{ $this->clearStepAction }}
                        @else
                            {{ $this->setStepAction }}
                        @endif
                    </div>
                </div>

                <div class="mt-5 border-t border-emerald-100/80 dark:border-emerald-900/50 pt-4">
                    <div class="flex flex-wrap items-center justify-between gap-2 mb-3">
                        <div class="text-sm font-medium text-gray-800 dark:text-gray-100">فعالیت‌های زیر این گام</div>
                        {{ $this->logActivityAction }}
                    </div>

                    @if ($activities->isEmpty())
                        <div class="rounded-xl border border-dashed border-gray-300 bg-white/70 px-4 py-6 text-center text-sm text-gray-500 dark:border-gray-600 dark:bg-gray-900/40">
                            فعالیتی ثبت نشده. از «ثبت فعالیت» برای تماس / جلسه استفاده کنید.
                        </div>
                    @else
                        <ul class="space-y-2">
                            @foreach ($activities as $activity)
                                <li class="rounded-xl border border-gray-200 bg-white px-4 py-3 dark:border-gray-700 dark:bg-gray-900">
                                    <div class="flex flex-wrap items-start justify-between gap-2">
                                        <div>
                                            <div class="flex flex-wrap items-center gap-2 text-xs text-gray-500 mb-1">
                                                <span class="rounded bg-gray-100 px-1.5 py-0.5 dark:bg-gray-800">
                                                    {{ \App\Models\Activity::typeOptions()[$activity->type] ?? $activity->type }}
                                                </span>
                                                @if ($activity->due_at)
                                                    <span>{{ \App\Support\Fa::dateTime($activity->due_at, 'Y/m/d H:i') }}</span>
                                                @endif
                                                @if ($activity->is_done)
                                                    <span class="text-emerald-700 dark:text-emerald-300">انجام‌شده</span>
                                                @endif
                                            </div>
                                            <div class="text-sm font-medium text-gray-900 dark:text-white">{{ $activity->subject }}</div>
                                            @if ($activity->body)
                                                <div class="mt-1 text-sm text-gray-600 dark:text-gray-300 whitespace-pre-line">{{ $activity->body }}</div>
                                            @endif
                                        </div>
                                        <div class="text-xs text-gray-400">{{ $activity->user?->name }}</div>
                                    </div>
                                </li>
                            @endforeach
                        </ul>
                    @endif
                </div>
            </div>
        </section>
    </div>
</x-filament-panels::page>
