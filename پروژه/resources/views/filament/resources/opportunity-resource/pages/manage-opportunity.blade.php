<x-filament-panels::page>
    @php
        $dash = '—';
        $hasOpen = $openStep !== null;
    @endphp

    <div class="grid grid-cols-1 gap-6 lg:grid-cols-3">
        <div class="space-y-6 lg:col-span-2">
            {{-- گام جاری --}}
            <x-filament::section>
                <x-slot name="heading">گام جاری</x-slot>

                <x-slot name="headerEnd">
                    @if ($hasOpen)
                        <div class="flex flex-wrap gap-2">
                            {{ $this->closeStepAction }}
                            {{ $this->clearStepAction }}
                        </div>
                    @else
                        {{ $this->setStepAction }}
                    @endif
                </x-slot>

                @if ($hasOpen)
                    <div class="space-y-1">
                        <p class="text-base font-semibold text-gray-950 dark:text-white">
                            {{ $openStep->title }}
                        </p>
                        <p class="text-sm text-gray-500 dark:text-gray-400">
                            @if ($dueLabel)
                                مهلت {{ \App\Support\Fa::digits($dueLabel) }}
                                @if ($isOverdue)
                                    <span class="font-medium text-danger-600 dark:text-danger-400">· عقب‌افتاده</span>
                                @endif
                            @endif
                            @if ($assigneeLabel && $assigneeLabel !== '—')
                                <span class="mx-1">·</span>
                                مسئول: {{ $assigneeLabel }}
                            @endif
                        </p>
                    </div>

                    <div class="mt-6">
                        <div class="mb-3 flex items-center justify-between gap-2">
                            <h3 class="text-sm font-medium text-gray-950 dark:text-white">فعالیت‌ها</h3>
                            {{ $this->logActivityAction }}
                        </div>

                        @if ($stepActivities->isEmpty())
                            <p class="text-sm text-gray-500 dark:text-gray-400">
                                فعالیتی ثبت نشده است.
                            </p>
                        @else
                            <ul class="divide-y divide-gray-200 dark:divide-white/10">
                                @foreach ($stepActivities as $activity)
                                    <li class="py-3">
                                        <div class="flex flex-wrap items-center justify-between gap-2 text-xs text-gray-500 dark:text-gray-400">
                                            <span>
                                                {{ \App\Models\Activity::typeOptions()[$activity->type] ?? $activity->type }}
                                                ·
                                                {{ $activity->due_at ? \App\Support\Fa::dateTime($activity->due_at, 'Y/m/d') : $dash }}
                                            </span>
                                            <span>{{ $activity->user?->name ?? $dash }}</span>
                                        </div>
                                        <p class="mt-1 text-sm font-medium text-gray-950 dark:text-white">
                                            {{ $activity->subject }}
                                        </p>
                                        @if (filled($activity->body))
                                            <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">
                                                {{ $activity->body }}
                                            </p>
                                        @endif
                                    </li>
                                @endforeach
                            </ul>
                        @endif
                    </div>
                @else
                    <p class="text-sm text-gray-500 dark:text-gray-400">
                        گام بعدی تعریف نشده است.
                    </p>
                @endif
            </x-filament::section>

            {{-- گام‌های قبلی --}}
            <x-filament::section>
                <x-slot name="heading">گام‌های قبلی</x-slot>

                @forelse ($closedSteps as $step)
                    @php $stepActs = $step->activities->sortByDesc('id'); @endphp
                    <details class="group border-b border-gray-200 py-3 last:border-b-0 dark:border-white/10">
                        <summary class="flex cursor-pointer list-none items-center justify-between gap-3 text-sm">
                            <div class="min-w-0">
                                <span class="font-medium text-gray-950 dark:text-white">{{ $step->title }}</span>
                                @if (filled($step->result))
                                    <p class="mt-0.5 truncate text-xs text-gray-500 dark:text-gray-400">
                                        {{ \Illuminate\Support\Str::limit($step->result, 80) }}
                                    </p>
                                @endif
                            </div>
                            <div class="flex shrink-0 items-center gap-2 text-xs text-gray-500 dark:text-gray-400">
                                <span>{{ $step->closed_at ? \App\Support\Fa::dateTime($step->closed_at, 'Y/m/d') : $dash }}</span>
                                @if ($stepActs->isNotEmpty())
                                    <span>{{ \App\Support\Fa::digits((string) $stepActs->count()) }} فعالیت</span>
                                @endif
                            </div>
                        </summary>
                        <div class="mt-3 space-y-2 text-sm">
                            @if (filled($step->result))
                                <p class="text-gray-600 dark:text-gray-300">
                                    <span class="font-medium text-gray-950 dark:text-white">نتیجه:</span>
                                    {{ $step->result }}
                                </p>
                            @endif
                            @foreach ($stepActs as $activity)
                                <div class="rounded-lg bg-gray-50 px-3 py-2 dark:bg-white/5">
                                    <div class="flex flex-wrap justify-between gap-2 text-xs text-gray-500 dark:text-gray-400">
                                        <span>
                                            {{ \App\Models\Activity::typeOptions()[$activity->type] ?? $activity->type }}
                                            ·
                                            {{ $activity->due_at ? \App\Support\Fa::dateTime($activity->due_at, 'Y/m/d') : $dash }}
                                        </span>
                                        <span>{{ $activity->user?->name ?? $dash }}</span>
                                    </div>
                                    <p class="mt-1 font-medium text-gray-950 dark:text-white">{{ $activity->subject }}</p>
                                    @if (filled($activity->body))
                                        <p class="mt-0.5 text-xs text-gray-500 dark:text-gray-400">{{ $activity->body }}</p>
                                    @endif
                                </div>
                            @endforeach
                        </div>
                    </details>
                @empty
                    <p class="text-sm text-gray-500 dark:text-gray-400">هنوز گام بسته‌شده‌ای نیست.</p>
                @endforelse
            </x-filament::section>
        </div>

        {{-- سایدبار --}}
        <div class="space-y-6">
            <x-filament::section>
                <x-slot name="heading">مخاطب</x-slot>

                <dl class="space-y-3 text-sm">
                    <div>
                        <dt class="text-gray-500 dark:text-gray-400">نام</dt>
                        <dd class="font-medium text-gray-950 dark:text-white">{{ $opportunity->contact_name ?: $dash }}</dd>
                    </div>
                    @if ($roleLabel)
                        <div>
                            <dt class="text-gray-500 dark:text-gray-400">نقش</dt>
                            <dd class="text-gray-950 dark:text-white">{{ $roleLabel }}</dd>
                        </div>
                    @endif
                    @if ($opportunity->organization_name)
                        <div>
                            <dt class="text-gray-500 dark:text-gray-400">سازمان</dt>
                            <dd class="text-gray-950 dark:text-white">{{ $opportunity->organization_name }}</dd>
                        </div>
                    @endif
                    @if ($opportunity->mobile)
                        <div>
                            <dt class="text-gray-500 dark:text-gray-400">موبایل</dt>
                            <dd>
                                <a href="tel:{{ $opportunity->mobile }}" class="text-primary-600 hover:underline dark:text-primary-400" dir="ltr">
                                    {{ \App\Support\Fa::digits($opportunity->mobile) }}
                                </a>
                            </dd>
                        </div>
                    @endif
                    @if ($opportunity->phone)
                        <div>
                            <dt class="text-gray-500 dark:text-gray-400">تلفن</dt>
                            <dd>
                                <a href="tel:{{ $opportunity->phone }}" class="text-primary-600 hover:underline dark:text-primary-400" dir="ltr">
                                    {{ \App\Support\Fa::digits($opportunity->phone) }}
                                </a>
                            </dd>
                        </div>
                    @endif
                    @if ($opportunity->email)
                        <div>
                            <dt class="text-gray-500 dark:text-gray-400">ایمیل</dt>
                            <dd>
                                <a href="mailto:{{ $opportunity->email }}" class="text-primary-600 hover:underline dark:text-primary-400" dir="ltr">
                                    {{ $opportunity->email }}
                                </a>
                            </dd>
                        </div>
                    @endif
                </dl>
            </x-filament::section>

            <x-filament::section>
                <x-slot name="heading">جزئیات فرصت</x-slot>

                <dl class="space-y-3 text-sm">
                    <div class="flex justify-between gap-3">
                        <dt class="text-gray-500 dark:text-gray-400">کد</dt>
                        <dd class="font-medium text-gray-950 dark:text-white">{{ \App\Support\Fa::digits($codeLabel) }}</dd>
                    </div>
                    <div class="flex justify-between gap-3">
                        <dt class="text-gray-500 dark:text-gray-400">محصول</dt>
                        <dd class="font-medium text-gray-950 dark:text-white">{{ $productLabel ?: $dash }}</dd>
                    </div>
                    <div class="flex justify-between gap-3">
                        <dt class="text-gray-500 dark:text-gray-400">مرحله قیف</dt>
                        <dd class="font-medium text-gray-950 dark:text-white">{{ $stageLabel }}</dd>
                    </div>
                    <div class="flex justify-between gap-3">
                        <dt class="text-gray-500 dark:text-gray-400">وضعیت</dt>
                        <dd class="font-medium text-gray-950 dark:text-white">{{ $statusLabel }}</dd>
                    </div>
                    <div class="flex justify-between gap-3">
                        <dt class="text-gray-500 dark:text-gray-400">مالک</dt>
                        <dd class="font-medium text-gray-950 dark:text-white">{{ $ownerLabel }}</dd>
                    </div>
                    <div class="flex justify-between gap-3">
                        <dt class="text-gray-500 dark:text-gray-400">پیگیری</dt>
                        <dd class="font-medium text-gray-950 dark:text-white">{{ $followupLabel }}</dd>
                    </div>
                </dl>
            </x-filament::section>
        </div>
    </div>

    <x-filament-actions::modals />
</x-filament-panels::page>
