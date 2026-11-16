<x-filament-panels::page>
    <div class="mx-auto flex w-full max-w-6xl flex-col gap-6" dir="rtl">
        <section class="rounded-2xl border border-gray-200 bg-white p-4 shadow-sm dark:border-gray-700 dark:bg-gray-900 sm:p-5">
            <div class="flex flex-col gap-2">
                <h2 class="text-base font-bold text-gray-950 dark:text-white">مدیریت تنظیمات فضای کاری</h2>
                <p class="text-sm leading-6 text-gray-500 dark:text-gray-400">
                    تنظیمات این صفحه فقط به شرکت جاری تعلق دارد. کلیدها را با ساختار نام‌گذاری مشخص تعریف کنید؛
                    مقادیر حساس رمزگذاری می‌شوند و مقدار ذخیره‌شده آن‌ها در فهرست نمایش داده نمی‌شود.
                </p>
            </div>
        </section>


        <section class="rounded-2xl border border-gray-200 bg-white p-4 shadow-sm dark:border-gray-700 dark:bg-gray-900 sm:p-5">
            <div class="mb-4">
                <h3 class="font-bold text-gray-950 dark:text-white">پوسته و هویت بصری شرکت</h3>
                <p class="mt-1 text-sm leading-6 text-gray-500 dark:text-gray-400">
                    پالت، رنگ اصلی، رنگ ثانویه و تراکم پنل فقط برای شرکت جاری ذخیره می‌شوند.
                </p>
            </div>

            <form wire:submit="saveTheme" class="space-y-4">
                <div class="grid gap-4 sm:grid-cols-2">
                    <div>
                        <label for="company-theme-palette" class="mb-1.5 block text-sm font-medium text-gray-700 dark:text-gray-300">پالت رنگ</label>
                        <select id="company-theme-palette" wire:model.live="themePalette" class="min-h-11 w-full rounded-lg border-gray-300 bg-white text-sm shadow-sm focus:border-primary-500 focus:ring-primary-500 dark:border-gray-700 dark:bg-gray-950 dark:text-white">
                            @foreach ($themePalettes as $value => $label)
                                <option value="{{ $value }}">{{ $label }}</option>
                            @endforeach
                            <option value="custom">رنگ دلخواه</option>
                        </select>
                        @error('themePalette') <p class="mt-1 text-xs text-danger-600">{{ $message }}</p> @enderror
                    </div>

                    <div>
                        <label for="company-theme-density" class="mb-1.5 block text-sm font-medium text-gray-700 dark:text-gray-300">تراکم نمایش</label>
                        <select id="company-theme-density" wire:model="themeDensity" class="min-h-11 w-full rounded-lg border-gray-300 bg-white text-sm shadow-sm focus:border-primary-500 focus:ring-primary-500 dark:border-gray-700 dark:bg-gray-950 dark:text-white">
                            <option value="comfortable">باز و خوانا</option>
                            <option value="balanced">متعادل</option>
                            <option value="compact">فشرده</option>
                        </select>
                        @error('themeDensity') <p class="mt-1 text-xs text-danger-600">{{ $message }}</p> @enderror
                    </div>

                    <div>
                        <label for="company-theme-primary" class="mb-1.5 block text-sm font-medium text-gray-700 dark:text-gray-300">رنگ اصلی</label>
                        <div class="flex min-h-11 items-center gap-3 rounded-lg border border-gray-300 px-3 dark:border-gray-700">
                            <input id="company-theme-primary" type="color" wire:model.live="themePrimary" class="size-8 cursor-pointer rounded border-0 bg-transparent p-0">
                            <span dir="ltr" class="font-mono text-sm text-gray-600 dark:text-gray-300">{{ $themePrimary }}</span>
                        </div>
                        @error('themePrimary') <p class="mt-1 text-xs text-danger-600">{{ $message }}</p> @enderror
                    </div>

                    <div>
                        <label for="company-theme-secondary" class="mb-1.5 block text-sm font-medium text-gray-700 dark:text-gray-300">رنگ ثانویه</label>
                        <div class="flex min-h-11 items-center gap-3 rounded-lg border border-gray-300 px-3 dark:border-gray-700">
                            <input id="company-theme-secondary" type="color" wire:model.live="themeSecondary" class="size-8 cursor-pointer rounded border-0 bg-transparent p-0">
                            <span dir="ltr" class="font-mono text-sm text-gray-600 dark:text-gray-300">{{ $themeSecondary }}</span>
                        </div>
                        @error('themeSecondary') <p class="mt-1 text-xs text-danger-600">{{ $message }}</p> @enderror
                    </div>
                </div>

                <div class="flex flex-col-reverse gap-2 border-t border-gray-100 pt-4 dark:border-gray-800 sm:flex-row sm:justify-end">
                    <x-filament::button type="button" color="gray" outlined wire:click="resetTheme">بازنشانی پوسته</x-filament::button>
                    <x-filament::button type="submit" icon="heroicon-m-swatch">ذخیره پوسته شرکت</x-filament::button>
                </div>
            </form>
        </section>

        <div class="grid gap-6 xl:grid-cols-[minmax(0,1fr)_minmax(20rem,0.85fr)]">
            <section class="order-2 overflow-hidden rounded-2xl border border-gray-200 bg-white shadow-sm dark:border-gray-700 dark:bg-gray-900 xl:order-1">
                <header class="border-b border-gray-100 px-4 py-4 dark:border-gray-800 sm:px-5">
                    <h3 class="font-bold text-gray-950 dark:text-white">تنظیمات ثبت‌شده</h3>
                    <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">{{ $settings->count() }} تنظیم</p>
                </header>
                <div class="divide-y divide-gray-100 dark:divide-gray-800">
                    @forelse ($settings as $setting)
                        <div wire:key="company-setting-{{ $setting->id }}" class="flex flex-col gap-3 px-4 py-4 sm:flex-row sm:items-center sm:justify-between sm:px-5">
                            <div class="min-w-0">
                                <div class="flex flex-wrap items-center gap-2">
                                    <code dir="ltr" class="break-all text-sm font-semibold text-gray-900 dark:text-gray-100">{{ $setting->key }}</code>
                                    @if ($setting->is_sensitive)
                                        <x-filament::badge color="warning">حساس</x-filament::badge>
                                    @endif
                                </div>
                                <div class="mt-2 flex flex-wrap gap-x-3 gap-y-1 text-xs text-gray-500 dark:text-gray-400">
                                    <span>نوع: {{ ['string' => 'متن', 'integer' => 'عدد صحیح', 'boolean' => 'بله/خیر', 'decimal' => 'عدد اعشاری', 'json' => 'JSON'][$setting->type] ?? $setting->type }}</span>
                                    <span>آخرین تغییر: {{ $setting->updated_at?->format('Y/m/d H:i') }}</span>
                                </div>
                            </div>
                            <div class="flex shrink-0 items-center gap-2">
                                <x-filament::button size="sm" color="gray" icon="heroicon-m-pencil-square" wire:click="editSetting({{ $setting->id }})">ویرایش</x-filament::button>
                                <x-filament::button size="sm" color="danger" outlined icon="heroicon-m-trash" wire:click="deleteSetting({{ $setting->id }})" wire:confirm="این تنظیم حذف شود؟">حذف</x-filament::button>
                            </div>
                        </div>
                    @empty
                        <div class="px-5 py-12 text-center">
                            <p class="font-semibold text-gray-800 dark:text-gray-200">هنوز تنظیمی ثبت نشده است</p>
                            <p class="mt-2 text-sm text-gray-500 dark:text-gray-400">از فرم مقابل برای تعریف اولین تنظیم شرکت استفاده کنید.</p>
                        </div>
                    @endforelse
                </div>
            </section>

            <section class="order-1 h-fit rounded-2xl border border-gray-200 bg-white p-4 shadow-sm dark:border-gray-700 dark:bg-gray-900 sm:p-5 xl:order-2">
                <div class="mb-5 flex items-center justify-between gap-3">
                    <h3 class="font-bold text-gray-950 dark:text-white">{{ $editingSettingId ? 'ویرایش تنظیم' : 'افزودن تنظیم' }}</h3>
                    @if ($editingSettingId)
                        <x-filament::badge color="info">در حال ویرایش</x-filament::badge>
                    @endif
                </div>

                <form wire:submit="saveSetting" class="space-y-4">
                    <div>
                        <label for="setting-key" class="mb-1.5 block text-sm font-medium text-gray-700 dark:text-gray-300">کلید تنظیم</label>
                        <input id="setting-key" type="text" dir="ltr" wire:model="key" @disabled($editingSettingId) placeholder="sales.default_currency" class="min-h-11 w-full rounded-lg border-gray-300 bg-white text-left text-sm shadow-sm focus:border-primary-500 focus:ring-primary-500 disabled:bg-gray-100 dark:border-gray-700 dark:bg-gray-950 dark:text-white dark:disabled:bg-gray-800">
                        @error('key') <p class="mt-1 text-xs text-danger-600">{{ $message }}</p> @enderror
                    </div>

                    <div>
                        <label for="setting-type" class="mb-1.5 block text-sm font-medium text-gray-700 dark:text-gray-300">نوع داده</label>
                        <select id="setting-type" wire:model="type" class="min-h-11 w-full rounded-lg border-gray-300 bg-white text-sm shadow-sm focus:border-primary-500 focus:ring-primary-500 dark:border-gray-700 dark:bg-gray-950 dark:text-white">
                            <option value="string">متن</option>
                            <option value="integer">عدد صحیح</option>
                            <option value="boolean">بله / خیر</option>
                            <option value="decimal">عدد اعشاری</option>
                            <option value="json">JSON</option>
                        </select>
                        @error('type') <p class="mt-1 text-xs text-danger-600">{{ $message }}</p> @enderror
                    </div>

                    <div>
                        <label for="setting-value" class="mb-1.5 block text-sm font-medium text-gray-700 dark:text-gray-300">مقدار</label>
                        @if ($type === 'boolean')
                            <select id="setting-value" wire:model="value" class="min-h-11 w-full rounded-lg border-gray-300 bg-white text-sm shadow-sm dark:border-gray-700 dark:bg-gray-950 dark:text-white">
                                <option value="">انتخاب کنید</option>
                                <option value="1">بله</option>
                                <option value="0">خیر</option>
                            </select>
                        @elseif ($type === 'json')
                            <textarea id="setting-value" wire:model="value" rows="6" dir="ltr" placeholder='{"enabled": true}' class="min-h-11 w-full rounded-lg border-gray-300 bg-white font-mono text-left text-sm shadow-sm focus:border-primary-500 focus:ring-primary-500 dark:border-gray-700 dark:bg-gray-950 dark:text-white"></textarea>
                        @else
                            <input id="setting-value" type="text" wire:model="value" @if ($type === 'integer') inputmode="numeric" @elseif ($type === 'decimal') inputmode="decimal" @endif dir="auto" placeholder="{{ $sensitive && $editingSettingId ? 'برای جایگزینی، مقدار جدید را وارد کنید' : 'مقدار تنظیم' }}" class="min-h-11 w-full rounded-lg border-gray-300 bg-white text-sm shadow-sm focus:border-primary-500 focus:ring-primary-500 dark:border-gray-700 dark:bg-gray-950 dark:text-white">
                        @endif
                        @if ($sensitive && $editingSettingId)
                            <p class="mt-1.5 text-xs leading-5 text-warning-600 dark:text-warning-400">مقدار فعلی نمایش داده نمی‌شود؛ برای تغییر آن مقدار جدید را وارد کنید.</p>
                        @endif
                        @error('value') <p class="mt-1 text-xs text-danger-600">{{ $message }}</p> @enderror
                    </div>

                    <div>
                        <label for="setting-validation-rules" class="mb-1.5 block text-sm font-medium text-gray-700 dark:text-gray-300">قواعد اعتبارسنجی (اختیاری)</label>
                        <textarea id="setting-validation-rules" wire:model="validationRules" rows="3" dir="ltr" placeholder='["min:1","max:100"]' class="min-h-11 w-full rounded-lg border-gray-300 bg-white font-mono text-left text-sm shadow-sm focus:border-primary-500 focus:ring-primary-500 dark:border-gray-700 dark:bg-gray-950 dark:text-white"></textarea>
                        <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">قواعد را به‌شکل آرایه JSON از رشته‌های قواعد Laravel وارد کنید.</p>
                        @error('validationRules') <p class="mt-1 text-xs text-danger-600">{{ $message }}</p> @enderror
                    </div>

                    <label class="flex items-start gap-3 rounded-xl border border-gray-200 p-3 dark:border-gray-700">
                        <input type="checkbox" wire:model="sensitive" class="mt-0.5 size-4 rounded border-gray-300 text-primary-600 focus:ring-primary-500">
                        <span>
                            <span class="block text-sm font-medium text-gray-800 dark:text-gray-200">این مقدار حساس است</span>
                            <span class="mt-1 block text-xs leading-5 text-gray-500 dark:text-gray-400">مقدار در پایگاه داده رمزگذاری می‌شود و در فهرست و Audit به‌صورت محرمانه باقی می‌ماند.</span>
                        </span>
                    </label>

                    <div class="flex flex-col-reverse gap-2 border-t border-gray-100 pt-4 dark:border-gray-800 sm:flex-row">
                        <x-filament::button type="submit" class="flex-1" icon="heroicon-m-check">ذخیره تنظیم</x-filament::button>
                        @if ($editingSettingId)
                            <x-filament::button type="button" color="gray" outlined wire:click="cancelEdit">انصراف</x-filament::button>
                        @endif
                    </div>
                </form>
            </section>
        </div>
    </div>
</x-filament-panels::page>
