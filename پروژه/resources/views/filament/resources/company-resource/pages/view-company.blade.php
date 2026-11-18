<x-filament-panels::page>
    <div class="space-y-6" dir="rtl">
        <section class="rounded-xl border border-gray-200 bg-white p-5 shadow-sm dark:border-gray-700 dark:bg-gray-900">
            <div class="mb-4 flex items-center justify-between gap-3">
                <h2 class="text-base font-semibold text-gray-950 dark:text-white">اطلاعات هویتی شرکت</h2>
                <span class="rounded-full px-3 py-1 text-xs {{ $company->is_active ? 'bg-success-50 text-success-700 dark:bg-success-400/10 dark:text-success-400' : 'bg-gray-100 text-gray-600 dark:bg-gray-800 dark:text-gray-300' }}">
                    {{ $company->is_active ? 'فعال' : 'غیرفعال' }}
                </span>
            </div>
            <dl class="grid grid-cols-1 gap-4 text-sm sm:grid-cols-2 xl:grid-cols-4">
                <div><dt class="text-gray-500 dark:text-gray-400">نام شرکت</dt><dd class="mt-1 font-medium text-gray-950 dark:text-white">{{ $company->name }}</dd></div>
                <div><dt class="text-gray-500 dark:text-gray-400">شناسه سیستمی</dt><dd class="mt-1 font-medium text-gray-950 dark:text-white" dir="ltr">{{ $company->slug }}</dd></div>
                <div><dt class="text-gray-500 dark:text-gray-400">شناسه ملی</dt><dd class="mt-1 font-medium text-gray-950 dark:text-white">{{ $company->national_id ?: '—' }}</dd></div>
                <div><dt class="text-gray-500 dark:text-gray-400">شماره ثبت</dt><dd class="mt-1 font-medium text-gray-950 dark:text-white">{{ $company->registration_number ?: '—' }}</dd></div>
                <div><dt class="text-gray-500 dark:text-gray-400">تلفن</dt><dd class="mt-1 font-medium text-gray-950 dark:text-white">{{ $company->phone ?: '—' }}</dd></div>
                <div><dt class="text-gray-500 dark:text-gray-400">ایمیل</dt><dd class="mt-1 font-medium text-gray-950 dark:text-white">{{ $company->email ?: '—' }}</dd></div>
                <div class="sm:col-span-2 xl:col-span-4"><dt class="text-gray-500 dark:text-gray-400">نشانی</dt><dd class="mt-1 font-medium text-gray-950 dark:text-white">{{ $company->address ?: '—' }}</dd></div>
            </dl>
        </section>

        <section class="rounded-xl border border-gray-200 bg-white p-5 shadow-sm dark:border-gray-700 dark:bg-gray-900">
            <h2 class="mb-4 text-base font-semibold text-gray-950 dark:text-white">مدیر شرکت</h2>
            @if ($manager)
                <dl class="grid grid-cols-1 gap-4 text-sm sm:grid-cols-3">
                    <div><dt class="text-gray-500 dark:text-gray-400">نام</dt><dd class="mt-1 font-medium text-gray-950 dark:text-white">{{ $manager->name }}</dd></div>
                    <div><dt class="text-gray-500 dark:text-gray-400">نام کاربری</dt><dd class="mt-1 font-medium text-gray-950 dark:text-white" dir="ltr">{{ $manager->username }}</dd></div>
                    <div><dt class="text-gray-500 dark:text-gray-400">ایمیل</dt><dd class="mt-1 font-medium text-gray-950 dark:text-white" dir="ltr">{{ $manager->email }}</dd></div>
                </dl>
            @else
                <p class="text-sm text-gray-500 dark:text-gray-400">برای این شرکت مدیر تعیین نشده است.</p>
            @endif
        </section>

        <section class="space-y-4">
            <div class="flex items-center justify-between gap-3">
                <h2 class="text-base font-semibold text-gray-950 dark:text-white">هویت‌های مالی و حساب‌های دریافت</h2>
                <span class="text-xs text-gray-500 dark:text-gray-400">اطلاعات نسخه‌دار؛ حساب پیش‌فرض صرفاً پیشنهاد است</span>
            </div>
            @forelse ($identities as $identity)
                <article class="rounded-xl border border-gray-200 bg-white p-5 shadow-sm dark:border-gray-700 dark:bg-gray-900">
                    <div class="mb-4 flex flex-wrap items-center justify-between gap-3">
                        <div class="flex items-center gap-2">
                            <h3 class="font-semibold text-gray-950 dark:text-white">{{ $identity->type === 'official' ? 'هویت مالی رسمی' : 'هویت مالی غیررسمی' }}</h3>
                            <span class="rounded-full bg-primary-50 px-2.5 py-1 text-xs text-primary-700 dark:bg-primary-400/10 dark:text-primary-300">نسخه {{ $identity->version }}</span>
                            @if (! $identity->is_active)
                                <span class="rounded-full bg-gray-100 px-2.5 py-1 text-xs text-gray-600 dark:bg-gray-800 dark:text-gray-300">غیرفعال</span>
                            @endif
                        </div>
                        <span class="text-sm font-medium text-gray-700 dark:text-gray-300">{{ $identity->legal_name }}</span>
                    </div>
                    <dl class="mb-5 grid grid-cols-1 gap-3 text-sm sm:grid-cols-2 xl:grid-cols-4">
                        <div><dt class="text-gray-500 dark:text-gray-400">شناسه ملی</dt><dd class="mt-1 text-gray-950 dark:text-white">{{ $identity->national_id ?: '—' }}</dd></div>
                        <div><dt class="text-gray-500 dark:text-gray-400">کد اقتصادی</dt><dd class="mt-1 text-gray-950 dark:text-white">{{ $identity->economic_code ?: '—' }}</dd></div>
                        <div><dt class="text-gray-500 dark:text-gray-400">تلفن</dt><dd class="mt-1 text-gray-950 dark:text-white">{{ $identity->phone ?: '—' }}</dd></div>
                        <div><dt class="text-gray-500 dark:text-gray-400">ایمیل</dt><dd class="mt-1 text-gray-950 dark:text-white">{{ $identity->email ?: '—' }}</dd></div>
                        <div class="sm:col-span-2 xl:col-span-4"><dt class="text-gray-500 dark:text-gray-400">نشانی</dt><dd class="mt-1 text-gray-950 dark:text-white">{{ $identity->address ?: '—' }}</dd></div>
                    </dl>
                    <div class="hidden overflow-x-auto rounded-lg border border-gray-200 dark:border-gray-700 md:block">
                        <table class="w-full min-w-[42rem] divide-y divide-gray-200 text-sm dark:divide-gray-700">
                            <thead class="bg-gray-50 dark:bg-gray-800">
                                <tr>
                                    <th class="px-3 py-2 text-right font-medium text-gray-600 dark:text-gray-300">عنوان</th>
                                    <th class="px-3 py-2 text-right font-medium text-gray-600 dark:text-gray-300">بانک</th>
                                    <th class="px-3 py-2 text-right font-medium text-gray-600 dark:text-gray-300">صاحب حساب</th>
                                    <th class="px-3 py-2 text-right font-medium text-gray-600 dark:text-gray-300">شماره حساب / شبا</th>
                                    <th class="px-3 py-2 text-right font-medium text-gray-600 dark:text-gray-300">وضعیت</th>
                                    <th class="px-3 py-2 text-right font-medium text-gray-600 dark:text-gray-300">نسخه</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-100 dark:divide-gray-800">
                                @forelse ($identity->bankAccounts as $account)
                                    <tr>
                                        <td class="px-3 py-2 text-gray-950 dark:text-white">{{ $account->label ?: '—' }}</td>
                                        <td class="px-3 py-2 text-gray-950 dark:text-white">{{ $account->bank_name }}</td>
                                        <td class="px-3 py-2 text-gray-950 dark:text-white">{{ $account->account_holder }}</td>
                                        <td class="px-3 py-2 text-gray-950 dark:text-white" dir="ltr">{{ $account->account_number ?: $account->iban ?: $account->card_number ?: '—' }}</td>
                                        <td class="px-3 py-2">
                                            @if ($account->is_active)
                                                <span class="text-success-600 dark:text-success-400">{{ $account->is_default ? 'فعال · پیش‌فرض' : 'فعال' }}</span>
                                            @else
                                                <span class="text-gray-500">غیرفعال</span>
                                            @endif
                                        </td>
                                        <td class="px-3 py-2 text-gray-950 dark:text-white">{{ $account->version }}</td>
                                    </tr>
                                @empty
                                    <tr><td colspan="6" class="px-3 py-4 text-center text-gray-500">برای این هویت حسابی ثبت نشده است.</td></tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                    <div class="space-y-3 md:hidden">
                        @forelse ($identity->bankAccounts as $account)
                            <div class="rounded-lg border border-gray-200 p-3 dark:border-gray-700">
                                <div class="mb-2 flex items-center justify-between gap-2">
                                    <strong class="text-sm text-gray-950 dark:text-white">{{ $account->label ?: $account->bank_name }}</strong>
                                    <span class="text-xs {{ $account->is_active ? 'text-success-600' : 'text-gray-500' }}">{{ $account->is_active ? ($account->is_default ? 'فعال · پیش‌فرض' : 'فعال') : 'غیرفعال' }}</span>
                                </div>
                                <dl class="grid grid-cols-2 gap-2 text-xs">
                                    <div><dt class="text-gray-500">بانک</dt><dd class="mt-1 text-gray-900 dark:text-gray-100">{{ $account->bank_name }}</dd></div>
                                    <div><dt class="text-gray-500">صاحب حساب</dt><dd class="mt-1 text-gray-900 dark:text-gray-100">{{ $account->account_holder }}</dd></div>
                                    <div class="col-span-2"><dt class="text-gray-500">شماره حساب / شبا</dt><dd class="mt-1 break-all text-gray-900 dark:text-gray-100" dir="ltr">{{ $account->account_number ?: $account->iban ?: $account->card_number ?: '—' }}</dd></div>
                                    <div><dt class="text-gray-500">نسخه</dt><dd class="mt-1 text-gray-900 dark:text-gray-100">{{ $account->version }}</dd></div>
                                </dl>
                            </div>
                        @empty
                            <p class="py-3 text-center text-sm text-gray-500">برای این هویت حسابی ثبت نشده است.</p>
                        @endforelse
                    </div>
                </article>
            @empty
                <div class="rounded-xl border border-dashed border-gray-300 p-6 text-center text-sm text-gray-500 dark:border-gray-700">هویت مالی برای این شرکت ثبت نشده است.</div>
            @endforelse
        </section>
    </div>
</x-filament-panels::page>
