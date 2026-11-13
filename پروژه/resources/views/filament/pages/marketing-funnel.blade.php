<x-filament-panels::page>
    <div class="mf-page">
        <div class="mf-toolbar">
            <div class="mf-stats">
                <div class="mf-stat">
                    <span class="mf-stat-label">کل در قیف</span>
                    <span class="mf-stat-value">{{ number_format($summary['total']) }}</span>
                </div>
                <div class="mf-stat">
                    <span class="mf-stat-label">فعال</span>
                    <span class="mf-stat-value">{{ number_format($summary['open']) }}</span>
                </div>
                <div class="mf-stat mf-stat--warn">
                    <span class="mf-stat-label">سررسید نزدیک</span>
                    <span class="mf-stat-value">{{ number_format($summary['due_soon']) }}</span>
                </div>
                <div class="mf-stat">
                    <span class="mf-stat-label">جمع مبلغ (ریال)</span>
                    <span class="mf-stat-value" dir="ltr">{{ number_format($summary['amount']) }}</span>
                </div>
            </div>
            <div class="mf-actions">
                <a href="{{ $listUrl }}" class="mf-btn mf-btn--ghost">لیست فرصت‌ها</a>
                <a href="{{ $createUrl }}" class="mf-btn mf-btn--ghost">فرم کامل</a>
                <a href="{{ $quickCreateUrl }}" class="mf-btn mf-btn--primary">ثبت سریع</a>
            </div>
        </div>

        <div class="mf-board">
            @foreach ($columns as $column)
                <section class="mf-col {{ $column['is_won'] ? 'is-won' : '' }} {{ $column['is_lost'] ? 'is-lost' : '' }}">
                    <header class="mf-col-head">
                        <div>
                            <h3 class="mf-col-title">{{ $column['name'] }}</h3>
                            @if ($column['description'])
                                <p class="mf-col-desc">{{ $column['description'] }}</p>
                            @endif
                        </div>
                        <div class="mf-col-meta">
                            <span class="mf-pill">{{ $column['count'] }}</span>
                            @if ($column['amount'] > 0)
                                <span class="mf-amount" dir="ltr">{{ number_format($column['amount']) }}</span>
                            @endif
                        </div>
                    </header>

                    <div class="mf-cards">
                        @forelse ($column['items'] as $item)
                            <a href="{{ $item['url'] }}" class="mf-card">
                                <div class="mf-card-title">{{ $item['title'] }}</div>
                                @if ($item['org'])
                                    <div class="mf-card-org">{{ $item['org'] }}</div>
                                @endif
                                <div class="mf-card-row">
                                    @if ($item['contact'])
                                        <span>{{ $item['contact'] }}</span>
                                    @endif
                                    @if ($item['owner'])
                                        <span class="mf-muted">{{ $item['owner'] }}</span>
                                    @endif
                                </div>
                                @if ($item['next_step'])
                                    <div class="mf-card-next">
                                        <span>{{ \Illuminate\Support\Str::limit($item['next_step'], 48) }}</span>
                                        @if ($item['due'])
                                            <span class="mf-due" dir="ltr">{{ $item['due'] }}</span>
                                        @endif
                                    </div>
                                @endif
                                @if ($item['amount'])
                                    <div class="mf-card-amount" dir="ltr">{{ number_format($item['amount']) }} ریال</div>
                                @endif
                            </a>
                        @empty
                            <p class="mf-empty">فرصتی در این مرحله نیست</p>
                        @endforelse
                    </div>
                </section>
            @endforeach
        </div>
    </div>
</x-filament-panels::page>
