<x-filament-panels::page>
    @php
        $dash = '—';
        $hasOpen = $openStep !== null;
        $relatedQuotes = $opportunity->relationLoaded('quotes')
            ? $opportunity->quotes
            : $opportunity->quotes()->orderByDesc('id')->get();
    @endphp

    

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
