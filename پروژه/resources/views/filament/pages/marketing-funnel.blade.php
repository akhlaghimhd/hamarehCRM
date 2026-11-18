<x-filament-panels::page>
    <div class="space-y-5" dir="rtl">
        <section class="grid grid-cols-2 gap-3 xl:grid-cols-4">
            @foreach ([
                ['کل فرصت‌ها', \App\Support\Fa::number($summary['total']), 'کل رکوردهای موجود در قیف'],
                ['فرصت‌های فعال', \App\Support\Fa::number($summary['open']), 'فرصت‌های در جریان'],
                ['سررسید نزدیک', \App\Support\Fa::number($summary['due_soon']), 'نیازمند پیگیری'],
                ['ارزش قیف', \App\Support\Fa::number($summary['amount']) . ' ریال', 'مجموع ارزش ثبت‌شده'],
            ] as [$label, $value, $hint])
                <article class="rounded-2xl border border-gray-200 bg-white p-4 shadow-sm dark:border-gray-700 dark:bg-gray-900">
                    <p class="text-xs font-medium text-gray-500 dark:text-gray-400">{{ $label }}</p>
                    <p class="mt-2 text-xl font-bold tracking-tight text-gray-950 dark:text-white">{{ $value }}</p>
                    <p class="mt-1 text-xs text-gray-400">{{ $hint }}</p>
                </article>
            @endforeach
        </section>

        <section class="flex flex-col gap-3 rounded-2xl border border-gray-200 bg-white p-3 shadow-sm dark:border-gray-700 dark:bg-gray-900 sm:flex-row sm:items-center sm:justify-between sm:p-4">
            <div>
                <h2 class="font-bold text-gray-900 dark:text-white">مسیر تبدیل فرصت‌ها</h2>
                <p class="mt-1 text-xs text-gray-500">وضعیت فرصت‌ها را در مراحل فروش دنبال کنید.</p>
            </div>
            <div class="grid grid-cols-3 gap-2 sm:flex">
                <a href="{{ $listUrl }}" class="inline-flex min-h-11 items-center justify-center rounded-xl border border-gray-200 px-3 text-xs font-semibold text-gray-700 hover:bg-gray-50 dark:border-gray-700 dark:text-gray-200 dark:hover:bg-gray-800">فهرست فرصت‌ها</a>
                <a href="{{ $createUrl }}" class="inline-flex min-h-11 items-center justify-center rounded-xl border border-gray-200 px-3 text-xs font-semibold text-gray-700 hover:bg-gray-50 dark:border-gray-700 dark:text-gray-200 dark:hover:bg-gray-800">فرم کامل</a>
                <a href="{{ $quickCreateUrl }}" class="inline-flex min-h-11 items-center justify-center rounded-xl bg-primary-600 px-3 text-xs font-semibold text-white hover:bg-primary-700">ثبت سریع</a>
            </div>
        </section>

        <div class="-mx-1 overflow-x-auto pb-3">
            <div class="flex min-w-max items-start gap-3 px-1">
                @foreach ($columns as $column)
                    <section class="w-[19rem] overflow-hidden rounded-2xl border border-gray-200 bg-gray-50 dark:border-gray-700 dark:bg-gray-950/60">
                        <header class="border-b border-gray-200 p-4 dark:border-gray-700">
                            <div class="flex items-start justify-between gap-3">
                                <div class="min-w-0">
                                    <h3 class="font-bold text-gray-900 dark:text-white">{{ $column['name'] }}</h3>
                                    @if ($column['description'])
                                        <p class="mt-1 text-xs leading-5 text-gray-500">{{ $column['description'] }}</p>
                                    @endif
                                </div>
                                <span class="inline-flex h-7 min-w-7 items-center justify-center rounded-lg bg-primary-100 px-2 text-xs font-bold text-primary-800 dark:bg-primary-950 dark:text-primary-200">{{ \App\Support\Fa::number($column['count']) }}</span>
                            </div>
                            @if ($column['amount'] > 0)
                                <p class="mt-3 text-xs font-semibold tabular-nums text-gray-500">{{ \App\Support\Fa::number($column['amount']) }} ریال</p>
                            @endif
                        </header>
                        <div class="space-y-2 p-3">
                            @forelse ($column['items'] as $item)
                                <a href="{{ $item['url'] }}" class="block rounded-xl border border-gray-200 bg-white p-3 shadow-sm transition hover:-translate-y-0.5 hover:border-primary-300 hover:shadow-md dark:border-gray-700 dark:bg-gray-900">
                                    <p class="font-semibold leading-6 text-gray-900 dark:text-white">{{ $item['title'] }}</p>
                                    @if ($item['org'])
                                        <p class="mt-1 text-xs text-gray-500">{{ $item['org'] }}</p>
                                    @endif
                                    <div class="mt-3 flex flex-wrap items-center justify-between gap-2 text-xs text-gray-500">
                                        @if ($item['contact']) <span>{{ $item['contact'] }}</span> @endif
                                        @if ($item['owner']) <span>{{ $item['owner'] }}</span> @endif
                                    </div>
                                    @if ($item['next_step'])
                                        <div class="mt-3 rounded-lg bg-gray-50 p-2 text-xs leading-5 text-gray-600 dark:bg-gray-800 dark:text-gray-300">
                                            {{ \Illuminate\Support\Str::limit($item['next_step'], 48) }}
                                            @if ($item['due'])
                                                <span class="mt-1 block font-semibold text-amber-700 dark:text-amber-300">{{ \App\Support\Fa::digits($item['due']) }}</span>
                                            @endif
                                        </div>
                                    @endif
                                    @if ($item['amount'])
                                        <p class="mt-3 border-t border-gray-100 pt-2 text-sm font-bold tabular-nums text-primary-700 dark:border-gray-800 dark:text-primary-300">{{ \App\Support\Fa::number($item['amount']) }} ریال</p>
                                    @endif
                                </a>
                            @empty
                                <div class="rounded-xl border border-dashed border-gray-300 px-3 py-8 text-center text-xs text-gray-400 dark:border-gray-700">در این مرحله فرصتی ثبت نشده است.</div>
                            @endforelse
                        </div>
                    </section>
                @endforeach
            </div>
        </div>
        <p class="text-center text-xs text-gray-400">برای مشاهده مراحل دیگر، صفحه را به‌صورت افقی پیمایش کنید.</p>
    </div>
</x-filament-panels::page>
