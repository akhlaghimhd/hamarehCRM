<x-filament-panels::page>
    <div class="sc-page">
        @if ($userMeta)
            <section class="sc-card">
                <div class="sc-card-head">
                    <h2 class="sc-card-title">کاربر جاری</h2>
                </div>
                <div class="sc-card-body">
                    <div class="sc-user-row">
                        <div class="sc-avatar" aria-hidden="true">{{ mb_substr($userMeta['name'], 0, 1) }}</div>
                        <div class="sc-user-meta">
                            <div class="sc-user-name">{{ $userMeta['name'] }}</div>
                            <div class="sc-user-sub">
                                <span dir="ltr">{{ '@' . $userMeta['username'] }}</span>
                                <span class="sc-dot" aria-hidden="true">·</span>
                                <span dir="ltr">{{ $userMeta['email'] }}</span>
                            </div>
                        </div>
                    </div>
                </div>
            </section>
        @endif

        @if ($currentCompany)
            <section class="sc-card sc-card--current">
                <div class="sc-card-head">
                    <h2 class="sc-card-title">شرکت فعال</h2>
                    @if ($currentCompany['is_active'])
                        <span class="sc-pill sc-pill--ok">فعال</span>
                    @else
                        <span class="sc-pill sc-pill--danger">غیرفعال</span>
                    @endif
                </div>
                <div class="sc-card-body">
                    <div class="sc-current-name">{{ $currentCompany['name'] }}</div>
                    <dl class="sc-meta-grid">
                        <div class="sc-meta-item">
                            <dt>نقش شما</dt>
                            <dd>{{ $currentCompany['role_label'] }}</dd>
                        </div>
                        <div class="sc-meta-item">
                            <dt>شناسه</dt>
                            <dd dir="ltr">{{ $currentCompany['slug'] }}</dd>
                        </div>
                        <div class="sc-meta-item">
                            <dt>شناسه ملی</dt>
                            <dd>{{ $currentCompany['national_id'] }}</dd>
                        </div>
                        <div class="sc-meta-item">
                            <dt>تلفن</dt>
                            <dd dir="ltr">{{ $currentCompany['phone'] }}</dd>
                        </div>
                    </dl>
                </div>
            </section>
        @endif

        <section class="sc-card">
            <div class="sc-card-head">
                <h2 class="sc-card-title">انتخاب شرکت</h2>
                <span class="sc-hint">{{ count($companyOptions) }} شرکت</span>
            </div>
            <div class="sc-card-body">
                <form wire:submit="switch" class="sc-form">
                    <div class="sc-options" role="radiogroup" aria-label="شرکت‌ها">
                        @forelse ($companyOptions as $option)
                            <label
                                class="sc-option {{ $option['is_current'] ? 'is-current' : '' }} {{ (int) $company_id === (int) $option['id'] ? 'is-selected' : '' }}"
                            >
                                <input
                                    class="sc-radio"
                                    type="radio"
                                    wire:model.live="company_id"
                                    value="{{ $option['id'] }}"
                                >
                                <div class="sc-option-body">
                                    <div class="sc-option-top">
                                        <span class="sc-option-name">{{ $option['name'] }}</span>
                                        <div class="sc-option-badges">
                                            @if ($option['is_current'])
                                                <span class="sc-pill sc-pill--warn">جاری</span>
                                            @endif
                                            @if ($option['is_default'])
                                                <span class="sc-pill sc-pill--muted">پیش‌فرض</span>
                                            @endif
                                            @unless ($option['is_active'])
                                                <span class="sc-pill sc-pill--danger">غیرفعال</span>
                                            @endunless
                                        </div>
                                    </div>
                                    <div class="sc-option-sub">
                                        <span>نقش: {{ $option['role_label'] }}</span>
                                        @if ($option['slug'])
                                            <span class="sc-dot" aria-hidden="true">·</span>
                                            <span dir="ltr">{{ $option['slug'] }}</span>
                                        @endif
                                        @if ($option['phone'])
                                            <span class="sc-dot" aria-hidden="true">·</span>
                                            <span dir="ltr">{{ $option['phone'] }}</span>
                                        @endif
                                    </div>
                                </div>
                            </label>
                        @empty
                            <p class="sc-empty">شرکت قابل‌انتخابی یافت نشد.</p>
                        @endforelse
                    </div>

                    <div class="sc-actions">
                        <x-filament::button type="submit" color="warning" size="md">
                            اعمال شرکت انتخاب‌شده
                        </x-filament::button>
                    </div>
                </form>
            </div>
        </section>
    </div>
</x-filament-panels::page>
