<x-filament-panels::page>
    @php
        /** @var \App\Models\Opportunity $opportunity */
        // $opportunity, $activities, $isOverdue, $dueLabel, $statusLabel, $stageLabel,
        // $productLabel, $ownerLabel, $codeLabel از getViewData() می‌آیند — نه propertyهای Livewire
        $dash = '—';

        $roleLabels = [
            'decision_maker' => 'تصمیم‌گیر',
            'influencer' => 'تأثیرگذار',
            'user' => 'کاربر نهایی',
            'gatekeeper' => 'دروازه‌بان',
            'other' => 'سایر',
        ];
        $roleLabel = $roleLabels[$opportunity->decision_role ?? ''] ?? null;
    @endphp

    <div class="space-y-6" dir="rtl">
        {{-- نوار وضعیت فشرده --}}
        <div class="flex flex-wrap items-center gap-2 rounded-xl border border-gray-200 bg-white px-4 py-3 shadow-sm dark:border-white/10 dark:bg-gray-900">
            <span class="inline-flex items-center rounded-full bg-gray-100 px-2.5 py-0.5 text-xs font-medium text-gray-700 dark:bg-white/10 dark:text-gray-200">
                {{ \App\Support\Fa::digits($codeLabel) }}
            </span>
            <span class="inline-flex items-center rounded-full bg-primary-50 px-2.5 py-0.5 text-xs font-medium text-primary-700 dark:bg-primary-400/10 dark:text-primary-300">
                {{ $statusLabel }}
            </span>
            <span class="inline-flex items-center rounded-full bg-amber-50 px-2.5 py-0.5 text-xs font-medium text-amber-800 dark:bg-amber-400/10 dark:text-amber-300">
                {{ $stageLabel }}
            </span>
            @if ($dueLabel)
                <span @class([
                    'inline-flex items-center gap-1 rounded-full px-2.5 py-0.5 text-xs font-medium',
                    'bg-danger-50 text-danger-700 dark:bg-danger-400/10 dark:text-danger-300' => $isOverdue,
                    'bg-gray-100 text-gray-700 dark:bg-white/10 dark:text-gray-200' => ! $isOverdue,
                ])>
                    مهلت: {{ \App\Support\Fa::digits($dueLabel) }}
                    @if ($isOverdue)
                        · عقب‌افتاده
                    @endif
                </span>
            @endif
            @if ($productLabel && $productLabel !== '—')
                <span class="inline-flex items-center rounded-full bg-gray-50 px-2.5 py-0.5 text-xs text-gray-600 dark:bg-white/5 dark:text-gray-300">
                    {{ $productLabel }}
                </span>
            @endif
        </div>

        <div class="grid grid-cols-1 gap-6 lg:grid-cols-12">
            {{-- ستون اصلی: کار --}}
            <div class="space-y-6 lg:col-span-8">
                {{-- گام جاری --}}
                <section class="overflow-hidden rounded-xl border border-gray-200 bg-white shadow-sm dark:border-white/10 dark:bg-gray-900">
                    <header class="flex flex-wrap items-center justify-between gap-3 border-b border-gray-100 px-4 py-3 dark:border-white/10">
                        <div class="flex items-center gap-2">
                            <x-filament::icon icon="heroicon-o-flag" class="h-5 w-5 text-primary-600 dark:text-primary-400" />
                            <h2 class="text-sm font-semibold text-gray-950 dark:text-white">گام جاری</h2>
                        </div>
                        <div class="flex flex-wrap items-center gap-2">
                            @if (filled($opportunity->next_step))
                                {{ $this->closeStepAction }}
                                {{ $this->clearStepAction }}
                            @else
                                {{ $this->setStepAction }}
                            @endif
                        </div>
                    </header>

                    <div class="px-4 py-4">
                        @if (filled($opportunity->next_step))
                            <p class="text-base font-medium leading-7 text-gray-900 dark:text-white">
                                {{ $opportunity->next_step }}
                            </p>
                            @if ($dueLabel)
                                <div @class([
                                    'mt-3 flex items-center gap-2 text-sm',
                                    'text-danger-600 dark:text-danger-400' => $isOverdue,
                                    'text-gray-500 dark:text-gray-400' => ! $isOverdue,
                                ])>
                                    <x-filament::icon icon="heroicon-o-calendar-days" class="h-4 w-4" />
                                    <span>{{ \App\Support\Fa::digits($dueLabel) }}</span>
                                    @if ($isOverdue)
                                        <strong class="font-semibold">عقب‌افتاده</strong>
                                    @endif
                                </div>
                            @endif
                        @else
                            <div class="rounded-lg border border-dashed border-gray-200 bg-gray-50 px-4 py-6 text-center dark:border-white/10 dark:bg-white/5">
                                <p class="text-sm font-medium text-gray-700 dark:text-gray-200">گام بعدی تعریف نشده.</p>
                                <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">با «تعریف گام» مشخص کنید قدم بعد چیست.</p>
                            </div>
                        @endif
                    </div>
                </section>

                {{-- فعالیت‌ها --}}
                <section class="overflow-hidden rounded-xl border border-gray-200 bg-white shadow-sm dark:border-white/10 dark:bg-gray-900">
                    <header class="flex flex-wrap items-center justify-between gap-3 border-b border-gray-100 px-4 py-3 dark:border-white/10">
                        <div class="flex items-center gap-2">
                            <x-filament::icon icon="heroicon-o-clipboard-document-list" class="h-5 w-5 text-primary-600 dark:text-primary-400" />
                            <h2 class="text-sm font-semibold text-gray-950 dark:text-white">
                                فعالیت‌ها
                                <span class="ms-1 rounded-md bg-gray-100 px-1.5 py-0.5 text-xs font-medium text-gray-600 dark:bg-white/10 dark:text-gray-300">
                                    {{ \App\Support\Fa::digits((string) $activities->count()) }}
                                </span>
                            </h2>
                        </div>
                        <div>
                            {{ $this->logActivityAction }}
                        </div>
                    </header>

                    <div class="divide-y divide-gray-100 dark:divide-white/5">
                        @forelse ($activities as $activity)
                            <article class="px-4 py-3">
                                <div class="flex flex-wrap items-center justify-between gap-2">
                                    <span class="inline-flex items-center rounded-md bg-gray-100 px-2 py-0.5 text-xs font-medium text-gray-700 dark:bg-white/10 dark:text-gray-200">
                                        {{ \App\Models\Activity::typeOptions()[$activity->type] ?? $activity->type }}
                                    </span>
                                    <time class="text-xs text-gray-500 dark:text-gray-400">
                                        {{ $activity->due_at ? \App\Support\Fa::dateTime($activity->due_at, 'Y/m/d') : $dash }}
                                    </time>
                                </div>
                                <p class="mt-2 text-sm font-medium text-gray-900 dark:text-white">{{ $activity->subject }}</p>
                                @if (filled($activity->body))
                                    <p class="mt-1 text-sm leading-6 text-gray-600 dark:text-gray-300">{{ $activity->body }}</p>
                                @endif
                                <div class="mt-2 flex flex-wrap items-center gap-2 text-xs text-gray-500 dark:text-gray-400">
                                    <span>{{ $activity->user?->name ?? $dash }}</span>
                                    @if ($activity->is_done)
                                        <span class="rounded-full bg-success-50 px-2 py-0.5 font-medium text-success-700 dark:bg-success-400/10 dark:text-success-300">انجام‌شده</span>
                                    @else
                                        <span class="rounded-full bg-amber-50 px-2 py-0.5 font-medium text-amber-800 dark:bg-amber-400/10 dark:text-amber-300">باز</span>
                                    @endif
                                </div>
                            </article>
                        @empty
                            <div class="px-4 py-8 text-center">
                                <p class="text-sm font-medium text-gray-700 dark:text-gray-200">هنوز فعالیتی ثبت نشده.</p>
                                <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">با «ثبت فعالیت» تماس، جلسه یا پیگیری را اینجا بنویسید.</p>
                            </div>
                        @endforelse
                    </div>
                </section>
            </div>

            {{-- سایدبار خلاصه --}}
            <aside class="space-y-6 lg:col-span-4">
                <section class="overflow-hidden rounded-xl border border-gray-200 bg-white shadow-sm dark:border-white/10 dark:bg-gray-900">
                    <header class="border-b border-gray-100 px-4 py-3 dark:border-white/10">
                        <h2 class="text-sm font-semibold text-gray-950 dark:text-white">مخاطب</h2>
                    </header>
                    <dl class="divide-y divide-gray-100 px-4 dark:divide-white/5">
                        <div class="flex justify-between gap-3 py-2.5 text-sm">
                            <dt class="shrink-0 text-gray-500 dark:text-gray-400">سازمان</dt>
                            <dd class="text-left font-medium text-gray-900 dark:text-white">{{ $opportunity->organization_name ?: $dash }}</dd>
                        </div>
                        <div class="flex justify-between gap-3 py-2.5 text-sm">
                            <dt class="shrink-0 text-gray-500 dark:text-gray-400">نام</dt>
                            <dd class="text-left font-medium text-gray-900 dark:text-white">{{ $opportunity->contact_name ?: $dash }}</dd>
                        </div>
                        <div class="flex justify-between gap-3 py-2.5 text-sm">
                            <dt class="shrink-0 text-gray-500 dark:text-gray-400">نقش</dt>
                            <dd class="text-left font-medium text-gray-900 dark:text-white">{{ $roleLabel ?: ($opportunity->contact_role ?: $dash) }}</dd>
                        </div>
                        <div class="flex justify-between gap-3 py-2.5 text-sm">
                            <dt class="shrink-0 text-gray-500 dark:text-gray-400">موبایل</dt>
                            <dd class="text-left font-medium text-gray-900 dark:text-white" dir="ltr">
                                @if ($opportunity->mobile)
                                    <a href="tel:{{ $opportunity->mobile }}" class="text-primary-600 hover:underline dark:text-primary-400">{{ \App\Support\Fa::digits($opportunity->mobile) }}</a>
                                @else
                                    {{ $dash }}
                                @endif
                            </dd>
                        </div>
                        <div class="flex justify-between gap-3 py-2.5 text-sm">
                            <dt class="shrink-0 text-gray-500 dark:text-gray-400">تلفن</dt>
                            <dd class="text-left font-medium text-gray-900 dark:text-white" dir="ltr">
                                @if ($opportunity->phone)
                                    <a href="tel:{{ $opportunity->phone }}" class="text-primary-600 hover:underline dark:text-primary-400">{{ \App\Support\Fa::digits($opportunity->phone) }}</a>
                                @else
                                    {{ $dash }}
                                @endif
                            </dd>
                        </div>
                        <div class="flex justify-between gap-3 py-2.5 text-sm">
                            <dt class="shrink-0 text-gray-500 dark:text-gray-400">ایمیل</dt>
                            <dd class="truncate text-left font-medium text-gray-900 dark:text-white">
                                @if ($opportunity->email)
                                    <a href="mailto:{{ $opportunity->email }}" class="text-primary-600 hover:underline dark:text-primary-400">{{ $opportunity->email }}</a>
                                @else
                                    {{ $dash }}
                                @endif
                            </dd>
                        </div>
                    </dl>
                </section>

                <section class="overflow-hidden rounded-xl border border-gray-200 bg-white shadow-sm dark:border-white/10 dark:bg-gray-900">
                    <header class="border-b border-gray-100 px-4 py-3 dark:border-white/10">
                        <h2 class="text-sm font-semibold text-gray-950 dark:text-white">جزئیات فرصت</h2>
                    </header>
                    <dl class="divide-y divide-gray-100 px-4 dark:divide-white/5">
                        <div class="flex justify-between gap-3 py-2.5 text-sm">
                            <dt class="shrink-0 text-gray-500 dark:text-gray-400">محصول</dt>
                            <dd class="text-left font-medium text-gray-900 dark:text-white">{{ $productLabel ?: $dash }}</dd>
                        </div>
                        <div class="flex justify-between gap-3 py-2.5 text-sm">
                            <dt class="shrink-0 text-gray-500 dark:text-gray-400">مرحله</dt>
                            <dd class="text-left font-medium text-gray-900 dark:text-white">{{ $stageLabel }}</dd>
                        </div>
                        <div class="flex justify-between gap-3 py-2.5 text-sm">
                            <dt class="shrink-0 text-gray-500 dark:text-gray-400">وضعیت</dt>
                            <dd class="text-left font-medium text-gray-900 dark:text-white">{{ $statusLabel }}</dd>
                        </div>
                        <div class="flex justify-between gap-3 py-2.5 text-sm">
                            <dt class="shrink-0 text-gray-500 dark:text-gray-400">مالک</dt>
                            <dd class="text-left font-medium text-gray-900 dark:text-white">{{ $ownerLabel }}</dd>
                        </div>
                        @if ($opportunity->amount)
                            <div class="flex justify-between gap-3 py-2.5 text-sm">
                                <dt class="shrink-0 text-gray-500 dark:text-gray-400">بودجه</dt>
                                <dd class="text-left font-medium text-gray-900 dark:text-white">{{ \App\Support\Fa::number($opportunity->amount) }} ریال</dd>
                            </div>
                        @endif
                        @if ($opportunity->source)
                            <div class="flex justify-between gap-3 py-2.5 text-sm">
                                <dt class="shrink-0 text-gray-500 dark:text-gray-400">منبع</dt>
                                <dd class="text-left font-medium text-gray-900 dark:text-white">{{ $opportunity->source }}</dd>
                            </div>
                        @endif
                    </dl>
                </section>
            </aside>
        </div>
    </div>

    <x-filament-actions::modals />
</x-filament-panels::page>
