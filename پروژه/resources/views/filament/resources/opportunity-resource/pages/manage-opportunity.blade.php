<x-filament-panels::page>
    @php
        $dash = '—';
        $hasOpen = $openStep !== null;
        $relatedQuotes = $opportunity->relationLoaded('quotes')
            ? $opportunity->quotes
            : $opportunity->quotes()->orderByDesc('id')->get();
    @endphp

    <style>
        .opp-ws { display: flex; flex-direction: column; gap: 1rem; }
        @media (min-width: 1024px) {
            .opp-ws { flex-direction: row; align-items: flex-start; }
            .opp-ws__main { flex: 1 1 0%; min-width: 0; order: 2; }
            .opp-ws__side { width: 20rem; flex: 0 0 20rem; order: 1; }
        }
        .opp-ws__stack { display: flex; flex-direction: column; gap: 1rem; }
        .opp-card {
            background: #fff;
            border: 1px solid #e5e7eb;
            border-radius: 1rem;
            box-shadow: 0 1px 2px rgba(0,0,0,.04);
        }
        .dark .opp-card { background: rgb(17 24 39); border-color: rgba(255,255,255,.1); }
        .opp-card__pad { padding: 1rem; }
        .opp-card__label { font-size: .75rem; font-weight: 500; color: #0d9488; margin-bottom: .35rem; }
        .dark .opp-card__label { color: #5eead4; }
        .opp-step {
            background: #ecfdf5;
            border: 1px solid #a7f3d0;
            border-radius: 1rem;
            padding: 1.25rem;
            box-shadow: 0 1px 2px rgba(0,0,0,.04);
        }
        .dark .opp-step { background: rgba(6,78,59,.25); border-color: rgba(6,95,70,.4); }
        .opp-step__top { display: flex; flex-wrap: wrap; gap: 1rem; justify-content: space-between; align-items: flex-start; }
        .opp-step__title-block { flex: 1 1 12rem; min-width: 0; }
        .opp-step__close-block { flex: 0 0 auto; max-width: 14rem; }
        .opp-step__label { font-size: .75rem; font-weight: 500; color: #065f46; margin-bottom: .25rem; }
        .dark .opp-step__label { color: #6ee7b7; }
        .opp-step__title { font-size: 1.125rem; font-weight: 600; line-height: 1.35; color: #030712; margin: 0; }
        .dark .opp-step__title { color: #fff; }
        .opp-step__meta { margin-top: .5rem; display: flex; flex-wrap: wrap; align-items: center; gap: .5rem; font-size: .875rem; color: #4b5563; }
        .dark .opp-step__meta { color: #d1d5db; }
        .opp-step__meta.is-overdue { color: #dc2626; font-weight: 600; }
        .opp-step__hint { margin-top: .5rem; font-size: .75rem; line-height: 1.4; color: #6b7280; }
        .opp-step__divider { margin: 1rem 0; border: 0; border-top: 1px dashed #a7f3d0; }
        .dark .opp-step__divider { border-color: rgba(6,95,70,.5); }
        .opp-step__acts-head { display: flex; flex-wrap: wrap; align-items: center; justify-content: space-between; gap: .5rem; margin-bottom: .75rem; }
        .opp-step__acts-title { font-size: .875rem; font-weight: 500; color: #1f2937; }
        .dark .opp-step__acts-title { color: #f3f4f6; }
        .opp-empty {
            background: rgba(255,255,255,.9);
            border-radius: .75rem;
            padding: 1.25rem 1rem;
            text-align: center;
            font-size: .875rem;
            color: #6b7280;
        }
        .dark .opp-empty { background: rgba(255,255,255,.05); color: #9ca3af; }
        .opp-pills { display: flex; flex-wrap: wrap; gap: .35rem; }
        .opp-pill {
            display: inline-flex; align-items: center;
            border-radius: 9999px; padding: .15rem .65rem;
            font-size: .75rem; background: #f3f4f6; color: #374151;
        }
        .dark .opp-pill { background: rgba(255,255,255,.1); color: #e5e7eb; }
        .opp-pill--ok { background: #ecfdf5; color: #065f46; }
        .opp-pill--stage { background: #fffbeb; color: #92400e; }
        .opp-pill--prod { background: #f0f9ff; color: #0c4a6e; }
        .dark .opp-pill--ok { background: rgba(6,78,59,.4); color: #a7f3d0; }
        .dark .opp-pill--stage { background: rgba(120,53,15,.4); color: #fde68a; }
        .dark .opp-pill--prod { background: rgba(12,74,110,.4); color: #bae6fd; }
        .opp-dl { margin: 0; }
        .opp-dl__row {
            display: flex; justify-content: space-between; gap: .75rem;
            padding: .65rem 1rem; font-size: .875rem;
            border-bottom: 1px solid #f9fafb;
        }
        .dark .opp-dl__row { border-color: rgba(255,255,255,.05); }
        .opp-dl__row:last-child { border-bottom: 0; }
        .opp-dl__k { color: #9ca3af; flex-shrink: 0; }
        .opp-dl__v { font-weight: 500; color: #030712; text-align: left; }
        .dark .opp-dl__v { color: #fff; }
        .opp-contact-name { font-size: 1rem; font-weight: 600; color: #030712; }
        .dark .opp-contact-name { color: #fff; }
        .opp-contact-sub { margin-top: .15rem; font-size: .75rem; color: #6b7280; }
        .opp-contact-phones { margin-top: .75rem; font-size: .875rem; color: #4b5563; line-height: 1.6; }
        .dark .opp-contact-phones { color: #d1d5db; }
        .opp-prev-head { padding: .75rem 1.25rem; }
        .opp-prev-head h2 { margin: 0; font-size: .875rem; font-weight: 600; color: #0f766e; }
        .dark .opp-prev-head h2 { color: #5eead4; }
        .opp-prev-item { border-top: 1px solid #f3f4f6; }
        .dark .opp-prev-item { border-color: rgba(255,255,255,.05); }
        .opp-prev-sum {
            display: flex; flex-wrap: wrap; align-items: center; justify-content: space-between; gap: .5rem;
            padding: .75rem 1.25rem; font-size: .875rem; cursor: pointer; list-style: none;
            background: #ecfdf580;
        }
        .dark .opp-prev-sum { background: rgba(6,78,59,.15); }
        .opp-prev-sum::-webkit-details-marker { display: none; }
        .opp-prev-body { padding: 1rem 1.25rem; font-size: .875rem; }
        .opp-act {
            border: 1px solid #f3f4f6; border-radius: .5rem; padding: .65rem .75rem; margin-top: .5rem;
        }
        .dark .opp-act { border-color: rgba(255,255,255,.1); }
        .opp-act__meta { display: flex; flex-wrap: wrap; justify-content: space-between; gap: .5rem; font-size: .75rem; color: #6b7280; }
        .opp-quote {
            display: block; border-top: 1px solid #f3f4f6; padding: .85rem 1.25rem; text-decoration: none;
            transition: background .15s ease;
        }
        .dark .opp-quote { border-color: rgba(255,255,255,.05); }
        .opp-quote:hover { background: #f9fafb; }
        .dark .opp-quote:hover { background: rgba(255,255,255,.04); }
        .opp-quote__top { display: flex; flex-wrap: wrap; justify-content: space-between; gap: .5rem; }
        .opp-quote__num { font-weight: 600; color: #0f766e; font-size: .875rem; }
        .dark .opp-quote__num { color: #5eead4; }
        .opp-quote__status { font-size: .75rem; color: #6b7280; }
        .opp-quote__title { margin-top: .25rem; font-size: .875rem; color: #111827; }
        .dark .opp-quote__title { color: #f3f4f6; }
        .opp-quote__total { margin-top: .35rem; font-size: .75rem; font-weight: 600; color: #0d9488; }
    </style>

    <div class="opp-ws" dir="rtl">
        <aside class="opp-ws__side">
            <div class="opp-ws__stack">
                <div class="opp-card">
                    <div class="opp-card__pad">
                        <div class="opp-card__label">مخاطب</div>
                        <div class="opp-contact-name">{{ $opportunity->contact_name ?: $dash }}</div>
                        @if ($roleLabel)
                            <div class="opp-contact-sub">{{ $roleLabel }}</div>
                        @endif
                        @if ($opportunity->organization_name)
                            <div class="opp-contact-sub">{{ $opportunity->organization_name }}</div>
                        @endif
                        <div class="opp-contact-phones">
                            @if ($opportunity->mobile)
                                <div dir="ltr">{{ \App\Support\Fa::digits($opportunity->mobile) }}</div>
                            @endif
                            @if ($opportunity->phone)
                                <div dir="ltr">{{ \App\Support\Fa::digits($opportunity->phone) }}</div>
                            @endif
                            @if ($opportunity->email)
                                <div>{{ $opportunity->email }}</div>
                            @endif
                        </div>
                    </div>
                </div>

                <div class="opp-card">
                    <div class="opp-card__pad" style="padding-bottom:.35rem">
                        <div class="opp-card__label" style="margin:0">جزئیات فرصت</div>
                    </div>
                    <dl class="opp-dl">
                        <div class="opp-dl__row">
                            <dt class="opp-dl__k">محصول</dt>
                            <dd class="opp-dl__v">{{ $productLabel ?: $dash }}</dd>
                        </div>
                        <div class="opp-dl__row">
                            <dt class="opp-dl__k">مرحله قیف</dt>
                            <dd class="opp-dl__v">{{ $stageLabel }}</dd>
                        </div>
                        <div class="opp-dl__row">
                            <dt class="opp-dl__k">وضعیت</dt>
                            <dd class="opp-dl__v">{{ $statusLabel }}</dd>
                        </div>
                        <div class="opp-dl__row">
                            <dt class="opp-dl__k">مالک</dt>
                            <dd class="opp-dl__v">{{ $ownerLabel }}</dd>
                        </div>
                        <div class="opp-dl__row">
                            <dt class="opp-dl__k">پیگیری</dt>
                            <dd class="opp-dl__v">{{ $followupLabel }}</dd>
                        </div>
                        @if ($opportunity->updated_at)
                            <div class="opp-dl__row">
                                <dt class="opp-dl__k">آخرین تعامل</dt>
                                <dd class="opp-dl__v">{{ \App\Support\Fa::dateTime($opportunity->updated_at, 'Y/m/d') }}</dd>
                            </div>
                        @endif
                        @if (filled($opportunity->main_objection) || filled($opportunity->purchase_use))
                            <div class="opp-dl__row" style="flex-direction:column;align-items:stretch">
                                <dt class="opp-dl__k">خلاصه</dt>
                                <dd class="opp-dl__v" style="text-align:right;font-weight:400;margin-top:.25rem">
                                    {{ $opportunity->main_objection ?: $opportunity->purchase_use }}
                                </dd>
                            </div>
                        @endif
                    </dl>
                </div>

                <div class="opp-card">
                    <div class="opp-prev-head">
                        <h2>پیش‌فاکتورها</h2>
                    </div>
                    @forelse ($relatedQuotes as $quote)
                        @php
                            $quoteUrl = \App\Filament\Resources\QuoteResource::getUrl('edit', ['record' => $quote]);
                            $statusLabelQuote = \App\Models\Quote::statusOptions()[$quote->status] ?? ($quote->status ?: $dash);
                        @endphp
                        <a href="{{ $quoteUrl }}" class="opp-quote">
                            <div class="opp-quote__top">
                                <span class="opp-quote__num">{{ \App\Support\Fa::digits($quote->number) }}</span>
                                <span class="opp-quote__status">{{ $statusLabelQuote }}</span>
                            </div>
                            <div class="opp-quote__title">{{ $quote->title }}</div>
                            @if ($quote->total)
                                <div class="opp-quote__total">{{ \App\Support\Fa::number($quote->total) }} ریال</div>
                            @endif
                        </a>
                    @empty
                        <div class="opp-empty" style="margin:0;border-radius:0;border-top:1px solid #f3f4f6">هنوز پیش‌فاکتوری از این فرصت ساخته نشده.</div>
                    @endforelse
                </div>

                <div class="opp-card">
                    <div class="opp-prev-head">
                        <h2>کارهای وابسته</h2>
                    </div>
                    @include('filament.resources.opportunity-resource.pages.partials.related-tasks')
                </div>
            </div>
        </aside>

        <section class="opp-ws__main">
            <div class="opp-ws__stack">
                <div class="opp-pills">
                    <span class="opp-pill">{{ \App\Support\Fa::digits($codeLabel) }}</span>
                    <span class="opp-pill opp-pill--ok">{{ $statusLabel }}</span>
                    <span class="opp-pill opp-pill--stage">{{ $stageLabel }}</span>
                    @if ($productLabel && $productLabel !== '—')
                        <span class="opp-pill opp-pill--prod">{{ $productLabel }}</span>
                    @endif
                </div>

                <div class="opp-step">
                    @if ($hasOpen)
                        <div class="opp-step__top">
                            <div class="opp-step__title-block">
                                <div class="opp-step__label">گام جاری</div>
                                <h2 class="opp-step__title">{{ $openStep->title }}</h2>
                                <div class="opp-step__meta {{ $isOverdue ? 'is-overdue' : '' }}">
                                    @if ($dueLabel)
                                        <span>
                                            مهلت {{ \App\Support\Fa::digits($dueLabel) }}
                                            @if ($isOverdue) · عقب‌افتاده @endif
                                        </span>
                                    @endif
                                    {{ $this->clearStepAction }}
                                </div>
                            </div>
                            <div class="opp-step__close-block">
                                {{ $this->closeStepAction }}
                                <p class="opp-step__hint">گام را با نتیجه ببندید؛ فعالیت جداگانه از دکمه زیر است.</p>
                            </div>
                        </div>

                        <hr class="opp-step__divider">

                        <div>
                            <div class="opp-step__acts-head">
                                <span class="opp-step__acts-title">فعالیت‌های زیر این گام</span>
                                {{ $this->logActivityAction }}
                            </div>

                            @if ($stepActivities->isEmpty())
                                <div class="opp-empty">
                                    فعالیتی ثبت نشده. از «+ ثبت فعالیت» برای تماس/جلسه استفاده کنید.
                                </div>
                            @else
                                <ul style="list-style:none;margin:0;padding:0;display:flex;flex-direction:column;gap:.5rem">
                                    @foreach ($stepActivities as $activity)
                                        <li class="opp-act" style="background:#fff">
                                            <div class="opp-act__meta">
                                                <span>
                                                    {{ \App\Models\Activity::typeOptions()[$activity->type] ?? $activity->type }}
                                                    ·
                                                    {{ $activity->due_at ? \App\Support\Fa::dateTime($activity->due_at, 'Y/m/d') : $dash }}
                                                </span>
                                                <span>{{ $activity->user?->name ?? $dash }}</span>
                                            </div>
                                            <p style="margin:.35rem 0 0;font-size:.875rem;font-weight:500;color:#030712">{{ $activity->subject }}</p>
                                            @if (filled($activity->body))
                                                <p style="margin:.25rem 0 0;font-size:.75rem;color:#6b7280;line-height:1.4">{{ $activity->body }}</p>
                                            @endif
                                        </li>
                                    @endforeach
                                </ul>
                            @endif
                        </div>
                    @else
                        <div class="opp-step__top">
                            <div class="opp-step__title-block">
                                <div class="opp-step__label">گام جاری</div>
                                <h2 class="opp-step__title" style="font-size:1rem;color:#374151">گام بعدی تعریف نشده</h2>
                            </div>
                            <div>{{ $this->setStepAction }}</div>
                        </div>
                    @endif
                </div>

                <div class="opp-card">
                    <div class="opp-prev-head">
                        <h2>گام‌های قبلی</h2>
                    </div>
                    @forelse ($closedSteps as $step)
                        @php $stepActs = $step->activities->sortByDesc('id'); @endphp
                        <details class="opp-prev-item" open>
                            <summary class="opp-prev-sum">
                                <span style="font-weight:500;color:#030712">{{ $step->title }}</span>
                                <span style="font-size:.75rem;color:#6b7280">
                                    {{ $step->closed_at ? \App\Support\Fa::dateTime($step->closed_at, 'Y/m/d') : $dash }}
                                    · {{ \App\Support\Fa::digits((string) $stepActs->count()) }} فعالیت
                                </span>
                            </summary>
                            <div class="opp-prev-body">
                                @if (filled($step->result))
                                    <p style="margin:0 0 .5rem;color:#4b5563;line-height:1.5">
                                        <strong style="color:#1f2937">نتیجه گام:</strong>
                                        {{ $step->result }}
                                    </p>
                                @endif
                                @foreach ($stepActs as $activity)
                                    <div class="opp-act">
                                        <div class="opp-act__meta">
                                            <span>
                                                {{ \App\Models\Activity::typeOptions()[$activity->type] ?? $activity->type }}
                                                ·
                                                {{ $activity->due_at ? \App\Support\Fa::dateTime($activity->due_at, 'Y/m/d') : $dash }}
                                            </span>
                                            <span>{{ $activity->user?->name ?? $dash }}</span>
                                        </div>
                                        <p style="margin:.35rem 0 0;font-weight:500">{{ $activity->subject }}</p>
                                        @if (filled($activity->body))
                                            <p style="margin:.2rem 0 0;font-size:.75rem;color:#6b7280">{{ $activity->body }}</p>
                                        @endif
                                    </div>
                                @endforeach
                            </div>
                        </details>
                    @empty
                        <div class="opp-empty" style="margin:0;border-radius:0;border-top:1px solid #f3f4f6">هنوز گام بسته‌شده‌ای نیست.</div>
                    @endforelse
                </div>
            </div>
        </section>
    </div>

    <x-filament-actions::modals />
</x-filament-panels::page>
