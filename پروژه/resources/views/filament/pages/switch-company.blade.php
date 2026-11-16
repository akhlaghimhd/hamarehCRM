<x-filament-panels::page>
    <div class="mx-auto flex w-full max-w-5xl flex-col gap-6" dir="rtl">
        @if ($userMeta)
            <section class="flex flex-col gap-4 rounded-2xl border border-gray-200 bg-white p-4 shadow-sm dark:border-gray-700 dark:bg-gray-900 sm:flex-row sm:items-center sm:p-5">
                <div class="flex size-12 shrink-0 items-center justify-center rounded-2xl bg-primary-50 text-lg font-bold text-primary-700 dark:bg-primary-950 dark:text-primary-200" aria-hidden="true">{{ mb_substr($userMeta['name'], 0, 1) }}</div>
                <div class="min-w-0 flex-1">
                    <p class="text-xs font-medium text-gray-500 dark:text-gray-400">حساب کاربری</p>
                    <h2 class="mt-1 text-base font-bold text-gray-950 dark:text-white">{{ $userMeta['name'] }}</h2>
                    <div class="mt-1 flex flex-wrap gap-x-3 gap-y-1 text-xs text-gray-500 dark:text-gray-400">
                        <span dir="ltr">{{ '@' . $userMeta['username'] }}</span>
                        <span dir="ltr">{{ $userMeta['email'] }}</span>
                    </div>
                </div>
                <div class="rounded-xl bg-gray-50 px-3 py-2 text-xs text-gray-500 dark:bg-gray-800 dark:text-gray-300">{{ count($companyOptions) }} عضویت سازمانی</div>
            </section>
        @endif

        @if ($currentCompany)
            <section class="overflow-hidden rounded-2xl border border-primary-200 bg-primary-50/60 shadow-sm dark:border-primary-900 dark:bg-primary-950/30">
                <div class="flex flex-wrap items-center justify-between gap-3 border-b border-primary-100 px-4 py-3 dark:border-primary-900 sm:px-5">
                    <div>
                        <p class="text-xs font-medium text-primary-700 dark:text-primary-300">فضای کاری فعال</p>
                        <h2 class="mt-1 text-lg font-bold text-gray-950 dark:text-white">{{ $currentCompany['name'] }}</h2>
                    </div>
                    <x-filament::badge color="success">فعال</x-filament::badge>
                </div>
                <dl class="grid grid-cols-2 gap-x-4 gap-y-4 p-4 sm:grid-cols-4 sm:px-5">
                    <div class="min-w-0"><dt class="text-xs text-gray-500 dark:text-gray-400">نقش شما</dt><dd class="mt-1 truncate text-sm font-semibold text-gray-900 dark:text-white">{{ $currentCompany['role_label'] }}</dd></div>
                    <div class="min-w-0"><dt class="text-xs text-gray-500 dark:text-gray-400">شناسه</dt><dd class="mt-1 truncate text-sm font-medium text-gray-800 dark:text-gray-200" dir="ltr">{{ $currentCompany['slug'] }}</dd></div>
                    <div class="min-w-0"><dt class="text-xs text-gray-500 dark:text-gray-400">شناسه ملی</dt><dd class="mt-1 text-sm font-medium text-gray-800 dark:text-gray-200">{{ $currentCompany['national_id'] }}</dd></div>
                    <div class="min-w-0"><dt class="text-xs text-gray-500 dark:text-gray-400">تلفن</dt><dd class="mt-1 text-sm font-medium text-gray-800 dark:text-gray-200" dir="ltr">{{ $currentCompany['phone'] }}</dd></div>
                </dl>
            </section>
        @endif

        <section class="overflow-hidden rounded-2xl border border-gray-200 bg-white shadow-sm dark:border-gray-700 dark:bg-gray-900">
            <header class="flex flex-col gap-1 border-b border-gray-100 px-4 py-4 dark:border-gray-800 sm:px-5">
                <h2 class="text-base font-bold text-gray-950 dark:text-white">انتخاب فضای کاری</h2>
                <p class="text-sm text-gray-500 dark:text-gray-400">شرکتی را انتخاب کنید که می‌خواهید اطلاعات و عملیات آن را مدیریت کنید. شرکت غیرفعال فقط توسط مدیر همان شرکت قابل بازیابی است.</p>
            </header>

            <div class="p-4 sm:p-5">
                <form wire:submit="switch" class="space-y-5">
                    <fieldset class="grid gap-3 lg:grid-cols-2">
                        <legend class="sr-only">شرکت‌های عضو</legend>
                        @forelse ($companyOptions as $option)
                            @if ($option['is_active'] && ! $option['is_deleted'])
                                <label class="group relative flex min-h-24 cursor-pointer items-start gap-3 rounded-xl border p-4 transition focus-within:ring-2 focus-within:ring-primary-500 {{ (int) $company_id === (int) $option['id'] ? 'border-primary-500 bg-primary-50/70 ring-1 ring-primary-500 dark:border-primary-400 dark:bg-primary-950/40' : 'border-gray-200 hover:border-gray-300 hover:bg-gray-50 dark:border-gray-700 dark:hover:border-gray-600 dark:hover:bg-gray-800/70' }}">
                                    <input type="radio" name="company_id" wire:model.live="company_id" value="{{ $option['id'] }}" class="mt-1 size-4 shrink-0 accent-primary-600">
                                    <span class="min-w-0 flex-1">
                                        <span class="flex flex-wrap items-center gap-2">
                                            <span class="font-bold text-gray-900 dark:text-white">{{ $option['name'] }}</span>
                                            @if ($option['is_current']) <x-filament::badge color="info">شرکت جاری</x-filament::badge> @endif
                                            @if ($option['is_default']) <x-filament::badge color="gray">پیش‌فرض</x-filament::badge> @endif
                                            <x-filament::badge color="success">فعال</x-filament::badge>
                                        </span>
                                        <span class="mt-2 flex flex-wrap gap-x-3 gap-y-1 text-xs text-gray-500 dark:text-gray-400">
                                            <span>نقش: {{ $option['role_label'] }}</span>
                                            @if ($option['slug']) <span dir="ltr">{{ $option['slug'] }}</span> @endif
                                            @if ($option['phone']) <span dir="ltr">{{ $option['phone'] }}</span> @endif
                                        </span>
                                    </span>
                                    <span class="mt-0.5 flex size-5 shrink-0 items-center justify-center rounded-full border border-gray-300 text-primary-600 dark:border-gray-600" aria-hidden="true">
                                        @if ((int) $company_id === (int) $option['id']) <span class="size-2.5 rounded-full bg-primary-600"></span> @endif
                                    </span>
                                </label>
                            @else
                                <div class="flex min-h-24 flex-col justify-between gap-3 rounded-xl border border-warning-200 bg-warning-50/60 p-4 dark:border-warning-900 dark:bg-warning-950/20 sm:flex-row sm:items-center">
                                    <div class="min-w-0">
                                        <div class="flex flex-wrap items-center gap-2">
                                            <span class="font-bold text-gray-900 dark:text-white">{{ $option['name'] }}</span>
                                            @if ($option['is_deleted'])
                                                <x-filament::badge color="danger">حذف‌شده</x-filament::badge>
                                            @else
                                                <x-filament::badge color="warning">غیرفعال</x-filament::badge>
                                            @endif
                                            @if ($option['is_default']) <x-filament::badge color="gray">پیش‌فرض</x-filament::badge> @endif
                                        </div>
                                        <p class="mt-2 text-xs text-gray-600 dark:text-gray-400">نقش شما: {{ $option['role_label'] }}</p>
                                        @if (! $option['can_activate'])
                                            <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">برای فعال‌سازی با مدیر شرکت تماس بگیرید.</p>
                                        @endif
                                    </div>
                                    @if ($option['can_activate'])
                                        <x-filament::button type="button" size="sm" color="primary" icon="heroicon-m-arrow-path" wire:click="activateCompany({{ $option['id'] }})" wire:confirm="این شرکت دوباره فعال و فضای کاری جاری شود؟">
                                            فعال‌سازی و ورود
                                        </x-filament::button>
                                    @endif
                                </div>
                            @endif
                        @empty
                            <p class="col-span-full rounded-xl border border-dashed border-gray-300 p-8 text-center text-sm text-gray-500 dark:border-gray-700">عضویت سازمانی برای این حساب پیدا نشد.</p>
                        @endforelse
                    </fieldset>

                    <div class="flex flex-col-reverse gap-3 border-t border-gray-100 pt-4 dark:border-gray-800 sm:flex-row sm:items-center sm:justify-between">
                        <p class="text-xs leading-6 text-gray-500 dark:text-gray-400">با تغییر شرکت، فضای کاری فعال و محدوده داده‌های پنل عوض می‌شود.</p>
                        <x-filament::button type="submit" color="primary" size="lg" icon="heroicon-m-arrow-left" @disabled(blank($company_id))>
                            ورود به فضای انتخاب‌شده
                        </x-filament::button>
                    </div>
                </form>
            </div>
        </section>
    </div>
</x-filament-panels::page>
