<x-filament-panels::page>
    @php
        $dash = '—';
        $hasOpen = $openStep !== null;
        $relatedQuotes = $opportunity->relationLoaded('quotes')
            ? $opportunity->quotes
            : $opportunity->quotes()->orderByDesc('id')->get();
    @endphp

    {{-- استایل خودکفا: وابسته به build جداگانه Vite نیست --}}
    <style>
        .opp-ws { display: flex; flex-direction: column; gap: 1rem; min-width: 0; }
        .opp-ws__stack { display: flex; flex-direction: column; gap: 1rem; min-width: 0; }
        .opp-ws__main, .opp-ws__side { min-width: 0; }
        .opp-card, .opp-step {
            min-width: 0;
            border: 1px solid rgba(0,0,0,.08);
            border-radius: .9rem;
            background: #fff;
            color: #111827;
            box-shadow: 0 1px 2px rgba(0,0,0,.04);
        }
        .dark .opp-card, .dark .opp-step {
            border-color: rgba(255,255,255,.1);
            background: rgba(255,255,255,.04);
            color: #f3f4f6;
        }
        .opp-card__pad { padding: 1rem; }
        .opp-card__label, .opp-prev-head h2 {
            margin: 0 0 .5rem;
            color: #0f766e;
            font-size: .8rem;
            font-weight: 700;
        }
        .opp-contact-name { font-size: 1rem; font-weight: 700; color: #111827; }
        .dark .opp-contact-name { color: #f9fafb; }
        .opp-contact-sub { margin-top: .25rem; color: #6b7280; font-size: .78rem; }
        .opp-contact-phones { margin-top: .75rem; color: #374151; font-size: .86rem; line-height: 1.8; }
        .dark .opp-contact-phones { color: #d1d5db; }
        .opp-dl { margin: 0; }
        .opp-dl__row {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            gap: .75rem;
            padding: .7rem 1rem;
            border-bottom: 1px solid rgba(0,0,0,.06);
            font-size: .85rem;
        }
        .dark .opp-dl__row { border-bottom-color: rgba(255,255,255,.08); }
        .opp-dl__row:last-child { border-bottom: 0; }
        .opp-dl__k { flex: 0 0 auto; color: #6b7280; }
        .opp-dl__v {
            min-width: 0;
            margin: 0;
            color: #111827;
            font-weight: 600;
            text-align: start;
            overflow-wrap: anywhere;
        }
        .dark .opp-dl__v { color: #f3f4f6; }
        .opp-pills { display: flex; flex-wrap: wrap; gap: .5rem; }
        .opp-pill {
            display: inline-flex;
            align-items: center;
            max-width: 100%;
            padding: .2rem .65rem;
            border: 1px solid rgba(0,0,0,.08);
            border-radius: 999px;
            background: #f9fafb;
            color: #374151;
            font-size: .78rem;
            font-weight: 600;
        }
        .dark .opp-pill { border-color: rgba(255,255,255,.1); background: rgba(255,255,255,.06); color: #e5e7eb; }
        .opp-pill--ok { border-color: rgba(16,185,129,.3); background: rgba(16,185,129,.1); color: #047857; }
        .opp-pill--stage { border-color: rgba(245,158,11,.35); background: rgba(245,158,11,.12); color: #b45309; }
        .opp-pill--prod { border-color: rgba(59,130,246,.3); background: rgba(59,130,246,.1); color: #1d4ed8; }
        .opp-step {
            padding: 1.25rem;
            border-color: rgba(16,185,129,.25);
            background: rgba(16,185,129,.05);
        }
        .dark .opp-step { background: rgba(16,185,129,.08); }
        .opp-step__top, .opp-step__acts-head, .opp-quote__top {
            display: flex;
            flex-wrap: wrap;
            align-items: flex-start;
            justify-content: space-between;
            gap: .75rem;
        }
        .opp-step__title-block { flex: 1 1 12rem; min-width: 0; }
        .opp-step__close-block { flex: 0 1 14rem; min-width: min(100%, 12rem); }
        .opp-step__label { margin-bottom: .25rem; color: #047857; font-size: .78rem; font-weight: 650; }
        .opp-step__title { margin: 0; font-size: 1.1rem; font-weight: 700; line-height: 1.6; overflow-wrap: anywhere; }
        .opp-step__meta {
            display: flex;
            flex-wrap: wrap;
            align-items: center;
            gap: .5rem;
            margin-top: .5rem;
            color: #4b5563;
            font-size: .82rem;
        }
        .opp-step__meta.is-overdue { color: #dc2626; font-weight: 700; }
        .opp-step__hint { display: block; margin: .35rem 0 0; color: #6b7280; font-size: .76rem; }
        .opp-step__divider {
            margin-block: 1rem;
            border: 0;
            border-top: 1px dashed rgba(16,185,129,.35);
        }
        .opp-step__acts-title { color: #374151; font-size: .88rem; font-weight: 650; }
        .dark .opp-step__acts-title { color: #e5e7eb; }
        .opp-empty {
            padding: 1.25rem 1rem;
            border: 1px dashed rgba(0,0,0,.12);
            border-radius: .75rem;
            background: #f9fafb;
            color: #6b7280;
            font-size: .85rem;
            line-height: 1.8;
            text-align: center;
        }
        .dark .opp-empty { border-color: rgba(255,255,255,.12); background: rgba(255,255,255,.04); color: #9ca3af; }
        .opp-prev-head { padding: .75rem 1rem; }
        .opp-prev-item, .opp-quote { border-top: 1px solid rgba(0,0,0,.06); }
        .dark .opp-prev-item, .dark .opp-quote { border-top-color: rgba(255,255,255,.08); }
        .opp-prev-sum {
            display: flex;
            flex-wrap: wrap;
            align-items: center;
            justify-content: space-between;
            gap: .5rem;
            padding: .75rem 1rem;
            background: #f9fafb;
            cursor: pointer;
            list-style: none;
        }
        .dark .opp-prev-sum { background: rgba(255,255,255,.04); }
        .opp-prev-sum::-webkit-details-marker { display: none; }
        .opp-prev-body { padding: 1rem; color: #4b5563; font-size: .86rem; line-height: 1.8; }
        .opp-act {
            margin-top: .5rem;
            padding: .75rem;
            border: 1px solid rgba(0,0,0,.08);
            border-radius: .6rem;
            background: #fff;
        }
        .dark .opp-act { border-color: rgba(255,255,255,.1); background: rgba(255,255,255,.04); }
        .opp-act__meta {
            display: flex;
            flex-wrap: wrap;
            justify-content: space-between;
            gap: .5rem;
            color: #6b7280;
            font-size: .76rem;
        }
        .opp-quote {
            display: block;
            padding: .75rem 1rem;
            color: inherit;
            text-decoration: none;
            transition: background-color 140ms ease;
        }
        .opp-quote:hover { background: rgba(15,118,110,.06); }
        .opp-quote__num, .opp-quote__total { color: #0f766e; font-size: .86rem; font-weight: 700; }
        .opp-quote__status { color: #6b7280; font-size: .76rem; }
        .opp-quote__title { margin-top: .25rem; font-size: .86rem; overflow-wrap: anywhere; }
        .opp-quote__total { margin-top: .5rem; font-size: .8rem; }
        @media (min-width: 1024px) {
            .opp-ws { flex-direction: row; align-items: flex-start; }
            .opp-ws__main { flex: 1 1 0%; order: 2; }
            .opp-ws__side { width: min(20rem, 30%); flex: 0 0 min(20rem, 30%); order: 1; }
        }
        @media (max-width: 767px) {
            .opp-step { padding: 1rem; }
            .opp-step__close-block { flex-basis: 100%; max-width: none; }
        }
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
                                <div dir="ltr" style="text-align:right">{{ \App\Support\Fa::digits($opportunity->mobile) }}</div>
                            @endif
                            @if ($opportunity->phone)
                                <div dir="ltr" style="text-align:right">{{ \App\Support\Fa::digits($opportunity->phone) }}</div>
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
                        <div class="opp-empty" style="margin:0;border-radius:0;border-top:1px solid rgba(0,0,0,.06)">هنوز پیش‌فاکتوری از این فرصت ساخته نشده.</div>
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
                                        <li class="opp-act">
                                            <div class="opp-act__meta">
                                                <span>
                                                    {{ \App\Models\Activity::typeOptions()[$activity->type] ?? $activity->type }}
                                                    ·
                                                    {{ $activity->due_at ? \App\Support\Fa::dateTime($activity->due_at, 'Y/m/d') : $dash }}
                                                </span>
                                                <span>{{ $activity->user?->name ?? $dash }}</span>
                                            </div>
                                            <p style="margin:.35rem 0 0;font-size:.875rem;font-weight:500">{{ $activity->subject }}</p>
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
                                <span style="font-weight:500">{{ $step->title }}</span>
                                <span style="font-size:.75rem;color:#6b7280">
                                    {{ $step->closed_at ? \App\Support\Fa::dateTime($step->closed_at, 'Y/m/d') : $dash }}
                                    · {{ \App\Support\Fa::digits((string) $stepActs->count()) }} فعالیت
                                </span>
                            </summary>
                            <div class="opp-prev-body">
                                @if (filled($step->result))
                                    <p style="margin:0 0 .5rem;line-height:1.5">
                                        <strong>نتیجه گام:</strong>
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
                        <div class="opp-empty" style="margin:0;border-radius:0;border-top:1px solid rgba(0,0,0,.06)">هنوز گام بسته‌شده‌ای نیست.</div>
                    @endforelse
                </div>
            </div>
        </section>
    </div>

    <x-filament-actions::modals />
</x-filament-panels::page>
