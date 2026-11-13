<x-filament-panels::page>
    @php
        /** @var \App\Models\Opportunity $opportunity */
        $roleLabels = [
            'decision_maker' => 'تصمیم‌گیر',
            'influencer' => 'تأثیرگذار',
            'user' => 'کاربر نهایی',
            'gatekeeper' => 'دروازه‌بان',
            'other' => 'سایر',
        ];
        $role = $opportunity->decision_role ?? null;
        $roleLabel = $roleLabels[$role] ?? null;
        $contactTitle = $opportunity->contact_name ?: $opportunity->organization_name ?: 'بدون مخاطب';
    @endphp

    <div class="opp-workspace" dir="rtl">
        {{-- نوار وضعیت --}}
        <div class="opp-status-bar">
            <div class="opp-status-bar__codes">
                <span class="opp-pill opp-pill--code">{{ $codeLabel }}</span>
                <span class="opp-pill opp-pill--muted">{{ $statusLabel }}</span>
                <span class="opp-pill opp-pill--stage">{{ $stageLabel }}</span>
                @if ($isOverdue)
                    <span class="opp-pill opp-pill--danger">عقب‌افتاده</span>
                @endif
            </div>
            @if ($dueLabel)
                <div class="opp-status-bar__due {{ $isOverdue ? 'is-overdue' : '' }}">
                    مهلت گام: <strong>{{ $dueLabel }}</strong>
                </div>
            @endif
        </div>

        <div class="opp-layout">
            {{-- ستون کار (اصلی) --}}
            <section class="opp-main">
                <div class="opp-card opp-card--step">
                    <div class="opp-card__head">
                        <div>
                            <div class="opp-kicker">گام جاری</div>
                            <h2 class="opp-step-title">
                                {{ $opportunity->next_step ?: 'گام بعدی تعریف نشده' }}
                            </h2>
                        </div>
                        <div class="opp-card__actions">
                            @if (filled($opportunity->next_step))
                                {{ $this->closeStepAction }}
                                {{ $this->clearStepAction }}
                            @else
                                {{ $this->setStepAction }}
                            @endif
                        </div>
                    </div>

                    @if ($dueLabel)
                        <div class="opp-step-meta">
                            <span>مهلت: {{ $dueLabel }}</span>
                            @if ($isOverdue)
                                <span class="opp-pill opp-pill--danger">عقب‌افتاده</span>
                            @endif
                        </div>
                    @endif

                    <div class="opp-activities">
                        <div class="opp-activities__head">
                            <span class="opp-kicker">فعالیت‌های این گام</span>
                            {{ $this->logActivityAction }}
                        </div>

                        @if ($activities->isEmpty())
                            <div class="opp-empty">
                                هنوز فعالیتی نیست. با «ثبت فعالیت» تماس، جلسه یا یادداشت بگذارید.
                            </div>
                        @else
                            <ul class="opp-activity-list">
                                @foreach ($activities as $activity)
                                    <li class="opp-activity">
                                        <div class="opp-activity__top">
                                            <div class="opp-activity__tags">
                                                <span class="opp-pill opp-pill--muted">
                                                    {{ \App\Models\Activity::typeOptions()[$activity->type] ?? $activity->type }}
                                                </span>
                                                @if ($activity->due_at)
                                                    <span class="opp-activity__date">{{ \App\Support\Fa::dateTime($activity->due_at, 'Y/m/d H:i') }}</span>
                                                @endif
                                                @if ($activity->is_done)
                                                    <span class="opp-pill opp-pill--ok">انجام‌شده</span>
                                                @endif
                                            </div>
                                            <span class="opp-activity__user">{{ $activity->user?->name }}</span>
                                        </div>
                                        <div class="opp-activity__subject">{{ $activity->subject }}</div>
                                        @if ($activity->body)
                                            <div class="opp-activity__body">{{ $activity->body }}</div>
                                        @endif
                                    </li>
                                @endforeach
                            </ul>
                        @endif
                    </div>
                </div>
            </section>

            {{-- سایدبار خلاصه --}}
            <aside class="opp-side">
                <div class="opp-card">
                    <div class="opp-kicker">مخاطب</div>
                    <div class="opp-contact-name">{{ $contactTitle }}</div>
                    @if ($opportunity->contact_name && $opportunity->organization_name)
                        <div class="opp-contact-org">{{ $opportunity->organization_name }}</div>
                    @endif
                    @if ($roleLabel)
                        <div class="opp-contact-role">{{ $roleLabel }}</div>
                    @endif
                    <div class="opp-contact-lines">
                        @if ($opportunity->mobile)
                            <div dir="ltr">{{ \App\Support\Fa::digits($opportunity->mobile) }}</div>
                        @endif
                        @if ($opportunity->phone)
                            <div dir="ltr">{{ \App\Support\Fa::digits($opportunity->phone) }}</div>
                        @endif
                        @if ($opportunity->email)
                            <div class="opp-truncate">{{ $opportunity->email }}</div>
                        @endif
                    </div>
                </div>

                <div class="opp-card">
                    <div class="opp-kicker">جزئیات فرصت</div>
                    <dl class="opp-dl">
                        <div>
                            <dt>محصول</dt>
                            <dd>{{ $productLabel ?: '—' }}</dd>
                        </div>
                        <div>
                            <dt>مرحله قیف</dt>
                            <dd>{{ $stageLabel }}</dd>
                        </div>
                        <div>
                            <dt>وضعیت</dt>
                            <dd>{{ $statusLabel }}</dd>
                        </div>
                        <div>
                            <dt>مالک</dt>
                            <dd>{{ $ownerLabel }}</dd>
                        </div>
                        @if ($opportunity->amount)
                            <div>
                                <dt>بودجه</dt>
                                <dd>{{ \App\Support\Fa::number($opportunity->amount) }} ریال</dd>
                            </div>
                        @endif
                        @if ($opportunity->source)
                            <div>
                                <dt>منبع</dt>
                                <dd>{{ $opportunity->source }}</dd>
                            </div>
                        @endif
                    </dl>
                </div>
            </aside>
        </div>
    </div>

    <x-filament-actions::modals />
</x-filament-panels::page>
