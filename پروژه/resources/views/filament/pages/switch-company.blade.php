<x-filament-panels::page>
    <div class="mx-auto w-full max-w-3xl space-y-6">

        {{-- کاربر --}}
        @if ($userMeta)
            <section class="rounded-xl border border-gray-200 bg-white p-5 shadow-sm dark:border-gray-700 dark:bg-gray-900">
                <div class="mb-3 text-xs font-medium tracking-wide text-gray-500">کاربر جاری</div>
                <div class="flex flex-wrap items-start justify-between gap-4">
                    <div>
                        <div class="text-base font-semibold text-gray-950 dark:text-white">{{ $userMeta['name'] }}</div>
                        <div class="mt-1 text-sm text-gray-600 dark:text-gray-300">{{ '@' . $userMeta['username'] }}</div>
                    </div>
                    <div class="text-sm text-gray-600 dark:text-gray-300">
                        <div>{{ $userMeta['email'] }}</div>
                        <div class="mt-1 text-xs text-gray-400">شناسه {{ $userMeta['id'] }}</div>
                    </div>
                </div>
            </section>
        @endif

        {{-- شرکت فعال --}}
        @if ($currentCompany)
            <section class="rounded-xl border border-amber-200 bg-amber-50/60 p-5 dark:border-amber-900/50 dark:bg-amber-950/20">
                <div class="mb-3 flex items-center justify-between gap-3">
                    <div class="text-xs font-medium tracking-wide text-amber-800 dark:text-amber-200">شرکت فعال</div>
                    @if ($currentCompany['is_active'])
                        <span class="rounded-full bg-emerald-100 px-2.5 py-0.5 text-xs font-medium text-emerald-800 dark:bg-emerald-900/40 dark:text-emerald-200">فعال</span>
                    @else
                        <span class="rounded-full bg-gray-200 px-2.5 py-0.5 text-xs font-medium text-gray-700">غیرفعال</span>
                    @endif
                </div>
                <div class="text-lg font-semibold text-gray-950 dark:text-white">{{ $currentCompany['name'] }}</div>
                <div class="mt-3 grid grid-cols-1 gap-2 sm:grid-cols-2 text-sm text-gray-700 dark:text-gray-300">
                    <div><span class="text-gray-500">نقش شما:</span> {{ $currentCompany['role_label'] }}</div>
                    <div><span class="text-gray-500">شناسه یکتا:</span> {{ $currentCompany['slug'] }}</div>
                    <div><span class="text-gray-500">شناسه ملی:</span> {{ $currentCompany['national_id'] }}</div>
                    <div><span class="text-gray-500">تلفن:</span> {{ $currentCompany['phone'] }}</div>
                    <div class="sm:col-span-2"><span class="text-gray-500">ایمیل:</span> {{ $currentCompany['email'] }}</div>
                </div>
            </section>
        @endif

        {{-- انتخاب --}}
        <section class="rounded-xl border border-gray-200 bg-white p-5 shadow-sm dark:border-gray-700 dark:bg-gray-900">
            <div class="mb-4">
                <div class="text-sm font-semibold text-gray-950 dark:text-white">انتخاب شرکت</div>
                <div class="mt-1 text-sm text-gray-500">فقط شرکت‌هایی که به حساب شما وصل شده‌اند.</div>
            </div>

            <form wire:submit="switch" class="space-y-3">
                @forelse (($companyOptions ?? []) as $option)
                    <label
                        class="flex cursor-pointer items-start gap-3 rounded-xl border px-4 py-3 transition
                            {{ $option['is_current']
                                ? 'border-amber-400 bg-amber-50/50 ring-1 ring-amber-300 dark:border-amber-700 dark:bg-amber-950/30'
                                : 'border-gray-200 hover:border-gray-300 dark:border-gray-700 dark:hover:border-gray-600' }}"
                    >
                        <input
                            type="radio"
                            wire:model.live="company_id"
                            value="{{ $option['id'] }}"
                            class="mt-1 h-4 w-4 accent-amber-500"
                        >
                        <div class="min-w-0 flex-1">
                            <div class="flex flex-wrap items-center gap-2">
                                <span class="text-sm font-semibold text-gray-950 dark:text-white">{{ $option['name'] }}</span>
                                @if ($option['is_current'])
                                    <span class="rounded-full bg-amber-100 px-2 py-0.5 text-[11px] font-medium text-amber-900 dark:bg-amber-900/50 dark:text-amber-100">جاری</span>
                                @endif
                                @if ($option['is_default'])
                                    <span class="rounded-full bg-gray-100 px-2 py-0.5 text-[11px] font-medium text-gray-700 dark:bg-gray-800 dark:text-gray-200">پیش‌فرض</span>
                                @endif
                                @unless ($option['is_active'])
                                    <span class="rounded-full bg-red-50 px-2 py-0.5 text-[11px] font-medium text-red-700">غیرفعال</span>
                                @endunless
                            </div>
                            <div class="mt-1 flex flex-wrap gap-x-4 gap-y-1 text-xs text-gray-500">
                                <span>نقش: {{ $option['role_label'] }}</span>
                                @if ($option['slug'])
                                    <span>{{ $option['slug'] }}</span>
                                @endif
                                @if ($option['phone'])
                                    <span>{{ $option['phone'] }}</span>
                                @endif
                            </div>
                        </div>
                    </label>
                @empty
                    <p class="text-sm text-red-600">شرکت قابل‌انتخابی یافت نشد.</p>
                @endforelse

                <div class="flex items-center justify-end gap-3 pt-2">
                    <x-filament::button type="submit" color="warning">
                        اعمال شرکت انتخاب‌شده
                    </x-filament::button>
                </div>
            </form>
        </section>
    </div>
</x-filament-panels::page>
