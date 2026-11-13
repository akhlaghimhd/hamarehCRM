<x-filament-panels::page>
    <div class="fi-sc fi-sc-has-gap gap-6" style="max-width: 42rem; margin-inline: auto;">

        @if ($userMeta)
            <x-filament::section>
                <x-slot name="heading">کاربر جاری</x-slot>

                <dl class="fi-sc-has-gap gap-3" style="display: grid; grid-template-columns: 7rem 1fr; align-items: baseline;">
                    <dt class="text-sm text-gray-500 dark:text-gray-400">نام</dt>
                    <dd class="text-sm font-medium text-gray-950 dark:text-white">{{ $userMeta['name'] }}</dd>

                    <dt class="text-sm text-gray-500 dark:text-gray-400">نام کاربری</dt>
                    <dd class="text-sm text-gray-950 dark:text-white" dir="ltr" style="text-align: right;">{{ '@' . $userMeta['username'] }}</dd>

                    <dt class="text-sm text-gray-500 dark:text-gray-400">ایمیل</dt>
                    <dd class="text-sm text-gray-950 dark:text-white" dir="ltr" style="text-align: right;">{{ $userMeta['email'] }}</dd>
                </dl>
            </x-filament::section>
        @endif

        @if ($currentCompany)
            <x-filament::section>
                <x-slot name="heading">شرکت فعال</x-slot>
                <x-slot name="description">{{ $currentCompany['is_active'] ? 'وضعیت: فعال' : 'وضعیت: غیرفعال' }}</x-slot>

                <div class="text-base font-semibold text-gray-950 dark:text-white" style="margin-bottom: 0.75rem;">
                    {{ $currentCompany['name'] }}
                </div>

                <dl class="fi-sc-has-gap gap-3" style="display: grid; grid-template-columns: 7rem 1fr; align-items: baseline;">
                    <dt class="text-sm text-gray-500 dark:text-gray-400">نقش شما</dt>
                    <dd class="text-sm text-gray-950 dark:text-white">{{ $currentCompany['role_label'] }}</dd>

                    <dt class="text-sm text-gray-500 dark:text-gray-400">شناسه یکتا</dt>
                    <dd class="text-sm text-gray-950 dark:text-white" dir="ltr" style="text-align: right;">{{ $currentCompany['slug'] }}</dd>

                    <dt class="text-sm text-gray-500 dark:text-gray-400">شناسه ملی</dt>
                    <dd class="text-sm text-gray-950 dark:text-white">{{ $currentCompany['national_id'] }}</dd>

                    <dt class="text-sm text-gray-500 dark:text-gray-400">تلفن</dt>
                    <dd class="text-sm text-gray-950 dark:text-white" dir="ltr" style="text-align: right;">{{ $currentCompany['phone'] }}</dd>

                    <dt class="text-sm text-gray-500 dark:text-gray-400">ایمیل</dt>
                    <dd class="text-sm text-gray-950 dark:text-white" dir="ltr" style="text-align: right;">{{ $currentCompany['email'] }}</dd>
                </dl>
            </x-filament::section>
        @endif

        <x-filament::section>
            <x-slot name="heading">انتخاب شرکت</x-slot>
            <x-slot name="description">فقط شرکت‌هایی که به حساب شما وصل شده‌اند.</x-slot>

            <form wire:submit="switch">
                <div class="fi-sc fi-sc-has-gap gap-3">
                    @forelse (($companyOptions ?? []) as $option)
                        <label
                            style="
                                display: flex;
                                align-items: flex-start;
                                gap: 0.75rem;
                                padding: 0.875rem 1rem;
                                border-radius: 0.75rem;
                                border: 1px solid {{ $option['is_current'] ? 'rgba(245, 158, 11, 0.55)' : 'rgba(0,0,0,0.08)' }};
                                background: {{ $option['is_current'] ? 'rgba(245, 158, 11, 0.08)' : 'transparent' }};
                                cursor: pointer;
                            "
                        >
                            <input
                                type="radio"
                                wire:model.live="company_id"
                                value="{{ $option['id'] }}"
                                style="margin-top: 0.2rem;"
                            >
                            <div style="flex: 1; min-width: 0;">
                                <div style="display: flex; flex-wrap: wrap; align-items: center; gap: 0.5rem;">
                                    <span class="text-sm font-semibold text-gray-950 dark:text-white">{{ $option['name'] }}</span>
                                    @if ($option['is_current'])
                                        <x-filament::badge color="warning" size="sm">جاری</x-filament::badge>
                                    @endif
                                    @if ($option['is_default'])
                                        <x-filament::badge color="gray" size="sm">پیش‌فرض</x-filament::badge>
                                    @endif
                                    @unless ($option['is_active'])
                                        <x-filament::badge color="danger" size="sm">غیرفعال</x-filament::badge>
                                    @endunless
                                </div>
                                <div class="text-xs text-gray-500 dark:text-gray-400" style="margin-top: 0.25rem; display: flex; flex-wrap: wrap; gap: 0.75rem;">
                                    <span>نقش: {{ $option['role_label'] }}</span>
                                    @if ($option['slug'])
                                        <span dir="ltr">{{ $option['slug'] }}</span>
                                    @endif
                                    @if ($option['phone'])
                                        <span dir="ltr">{{ $option['phone'] }}</span>
                                    @endif
                                </div>
                            </div>
                        </label>
                    @empty
                        <p class="text-sm text-danger-600">شرکت قابل‌انتخابی یافت نشد.</p>
                    @endforelse
                </div>

                <div style="margin-top: 1.25rem; display: flex; justify-content: flex-end;">
                    <x-filament::button type="submit" color="warning">
                        اعمال شرکت انتخاب‌شده
                    </x-filament::button>
                </div>
            </form>
        </x-filament::section>
    </div>
</x-filament-panels::page>
