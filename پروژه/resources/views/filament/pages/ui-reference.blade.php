<x-filament-panels::page>
<div class="space-y-6" dir="rtl">
    <section class="relative isolate overflow-hidden rounded-2xl border border-teal-200 bg-gradient-to-bl from-white via-teal-50 to-slate-50 p-5 shadow-sm dark:border-teal-900 dark:from-gray-900 dark:via-teal-950/40 dark:to-gray-900 sm:p-8">
        <div class="relative z-10 max-w-3xl space-y-3">
            <div class="flex flex-wrap gap-2"><x-filament::badge color="info">نسخه نمایشی</x-filament::badge><x-filament::badge color="gray">hamareh-ds</x-filament::badge></div>
            <h1 class="text-xl font-bold tracking-tight text-gray-950 dark:text-white sm:text-2xl">مرجع طراحی و تجربه کاربری هم‌ره</h1>
            <p class="text-sm leading-7 text-gray-600 dark:text-gray-300">این صفحه برای ارزیابی زبان بصری، تراکم اطلاعات و رفتار اجزای رابط ساخته شده است. تمام رکوردها و اعداد ساختگی‌اند و به اطلاعات واقعی شرکت متصل نیستند.</p>
            <div class="flex flex-wrap gap-2 text-xs font-medium text-gray-600 dark:text-gray-300">
                @foreach (['۳۶۰ px','۳۹۰ px','۴۳۰ px','۷۶۸ px','۱۰۲۴ px','دسکتاپ'] as $viewport)<span class="rounded-lg border border-gray-200 bg-white/80 px-3 py-2 dark:border-gray-700 dark:bg-gray-900/70">{{ $viewport }}</span>@endforeach
            </div>
        </div>
        <div aria-hidden="true" class="pointer-events-none absolute -left-12 -top-16 size-64 rounded-full border border-teal-300/50 dark:border-teal-700/40"></div>
    </section>

    <section class="space-y-3">
        <div><h2 class="text-base font-bold text-gray-950 dark:text-white">شاخص‌ها و کارت‌های خلاصه</h2><p class="mt-1 text-xs text-gray-500 dark:text-gray-400">نمونه نمایشی؛ نه آمار عملیاتی</p></div>
        <div class="grid grid-cols-1 gap-3 sm:grid-cols-2 xl:grid-cols-4">
            @foreach ([['فرصت‌های باز','۱۲۸','text-teal-700 dark:text-teal-300'],['فعالیت امروز','۳۴','text-sky-700 dark:text-sky-300'],['پیش‌فاکتورهای منتظر','۹','text-amber-700 dark:text-amber-300'],['ارزش برآوردی','۴۸۷٫۵ م','text-violet-700 dark:text-violet-300']] as $stat)
                <article class="rounded-xl border border-gray-200 bg-white p-4 shadow-sm dark:border-gray-800 dark:bg-gray-900"><p class="text-sm font-medium text-gray-500 dark:text-gray-400">{{ $stat[0] }}</p><p class="mt-3 text-2xl font-bold tabular-nums {{ $stat[2] }}">{{ $stat[1] }}</p><p class="mt-2 text-xs text-gray-400 dark:text-gray-500">مقدار ساختگی</p></article>
            @endforeach
        </div>
    </section>

    <section class="space-y-3">
        <div><h2 class="text-base font-bold text-gray-950 dark:text-white">رنگ، وضعیت و تایپوگرافی</h2><p class="mt-1 text-xs text-gray-500 dark:text-gray-400">رنگ‌ها باید در تم روشن و تاریک خوانا بمانند.</p></div>
        <div class="grid grid-cols-2 gap-2 sm:grid-cols-4 xl:grid-cols-8">
            @foreach ([['اصلی','bg-teal-600'],['ثانویه','bg-slate-600'],['موفقیت','bg-emerald-600'],['هشدار','bg-amber-500'],['خطا','bg-rose-600'],['اطلاع','bg-sky-600'],['خنثی','bg-gray-400'],['تأکید','bg-violet-600']] as $swatch)
                <div class="rounded-xl border border-gray-200 bg-white p-2 dark:border-gray-800 dark:bg-gray-900"><div class="h-9 rounded-lg {{ $swatch[1] }}"></div><p class="mt-2 text-center text-xs font-medium text-gray-700 dark:text-gray-300">{{ $swatch[0] }}</p></div>
            @endforeach
        </div>
        <div class="rounded-xl border border-gray-200 bg-white p-4 dark:border-gray-800 dark:bg-gray-900">
            <p class="text-xs text-gray-500 dark:text-gray-400">عنوان صفحه · ۲۴ پیکسل</p><p class="mt-1 text-xl font-bold text-gray-950 dark:text-white">مدیریت ارتباط با مشتریان</p>
            <p class="mt-3 text-xs text-gray-500 dark:text-gray-400">متن بدنه · ۱۴ پیکسل · اعداد نمونه: ۱۲۳۴۵۶۷۸۹۰</p><p class="mt-1 text-sm leading-7 text-gray-700 dark:text-gray-300">خوانایی متن فارسی، ترکیب واژه‌های فارسی و English و نمایش صحیح اعداد باید در همه اندازه‌ها بررسی شود.</p>
        </div>
    </section>

    <section class="space-y-3">
        <div><h2 class="text-base font-bold text-gray-950 dark:text-white">دکمه‌ها و نشانگرها</h2><p class="mt-1 text-xs text-gray-500 dark:text-gray-400">کنترل‌ها فقط برای مشاهده ظاهر هستند.</p></div>
        <div class="flex flex-wrap items-center gap-2 rounded-xl border border-gray-200 bg-white p-4 dark:border-gray-800 dark:bg-gray-900">
            <x-filament::button icon="heroicon-m-plus" disabled>عملیات اصلی</x-filament::button><x-filament::button color="gray" icon="heroicon-m-pencil-square" disabled>ویرایش</x-filament::button><x-filament::button color="success" icon="heroicon-m-check" disabled>تأیید</x-filament::button><x-filament::button color="warning" icon="heroicon-m-clock" disabled>در انتظار</x-filament::button><x-filament::button color="danger" icon="heroicon-m-trash" disabled>حذف</x-filament::button>
            <x-filament::badge color="success">موفق</x-filament::badge><x-filament::badge color="warning">نیازمند پیگیری</x-filament::badge><x-filament::badge color="danger">دارای خطا</x-filament::badge><x-filament::badge color="info">در جریان</x-filament::badge><x-filament::badge color="gray">پیش‌نویس</x-filament::badge>
        </div>
    </section>

    <section class="space-y-3">
        <div><h2 class="text-base font-bold text-gray-950 dark:text-white">فرم و فیلدهای ورودی</h2><p class="mt-1 text-xs text-gray-500 dark:text-gray-400">در موبایل تک‌ستونی و با فضای لمس کافی.</p></div>
        <div class="rounded-xl border border-gray-200 bg-white p-4 dark:border-gray-800 dark:bg-gray-900 sm:p-5">
            <div class="grid grid-cols-1 gap-4 md:grid-cols-2">
                <label class="block space-y-1.5"><span class="text-sm font-medium text-gray-700 dark:text-gray-300">عنوان نمونه</span><input type="text" value="تأمین تجهیزات اداری" readonly class="w-full rounded-lg border border-gray-300 bg-white px-3 py-2.5 text-sm text-gray-800 dark:border-gray-700 dark:bg-gray-950 dark:text-gray-200"></label>
                <label class="block space-y-1.5"><span class="text-sm font-medium text-gray-700 dark:text-gray-300">وضعیت</span><select disabled class="w-full rounded-lg border border-gray-300 bg-white px-3 py-2.5 text-sm text-gray-700 dark:border-gray-700 dark:bg-gray-950 dark:text-gray-300"><option>در حال مذاکره</option></select></label>
                <label class="block space-y-1.5"><span class="text-sm font-medium text-gray-700 dark:text-gray-300">تاریخ پیگیری</span><input type="text" value="۱۴۰۵/۰۸/۲۴" readonly class="w-full rounded-lg border border-gray-300 bg-white px-3 py-2.5 text-sm text-gray-800 dark:border-gray-700 dark:bg-gray-950 dark:text-gray-200"></label>
                <label class="block space-y-1.5"><span class="text-sm font-medium text-gray-700 dark:text-gray-300">مبلغ برآوردی</span><input type="text" value="۲٬۱۰۰٬۰۰۰٬۰۰۰" readonly class="w-full rounded-lg border border-gray-300 bg-white px-3 py-2.5 text-sm text-gray-800 dark:border-gray-700 dark:bg-gray-950 dark:text-gray-200"></label>
                <label class="block space-y-1.5 md:col-span-2"><span class="text-sm font-medium text-gray-700 dark:text-gray-300">یادداشت</span><textarea rows="3" readonly class="w-full rounded-lg border border-gray-300 bg-white px-3 py-2.5 text-sm leading-6 text-gray-800 dark:border-gray-700 dark:bg-gray-950 dark:text-gray-200">نمونه متن چندخطی و ارتفاع مناسب در موبایل.</textarea></label>
            </div>
            <div class="mt-4 flex flex-wrap gap-2"><x-filament::button disabled>ذخیره (غیرفعال در نمونه)</x-filament::button><x-filament::button color="gray" disabled>انصراف</x-filament::button></div>
        </div>
    </section>

    <section class="space-y-3">
        <div class="flex flex-wrap items-end justify-between gap-2"><div><h2 class="text-base font-bold text-gray-950 dark:text-white">جدول دسکتاپ و کارت موبایل</h2><p class="mt-1 text-xs text-gray-500 dark:text-gray-400">یک داده نمایشی با دو شیوه ارائه متناسب با اندازه نمایشگر.</p></div><x-filament::badge color="info">الگوی پیشنهادی</x-filament::badge></div>
        <div class="hidden overflow-hidden rounded-xl border border-gray-200 bg-white dark:border-gray-800 dark:bg-gray-900 lg:block">
            <div class="overflow-x-auto"><table class="w-full min-w-[760px] text-right text-sm">
                <thead class="bg-gray-50 text-xs font-semibold text-gray-500 dark:bg-gray-800 dark:text-gray-300"><tr><th class="px-4 py-3">شناسه</th><th class="px-4 py-3">عنوان فرصت</th><th class="px-4 py-3">مشتری</th><th class="px-4 py-3">مرحله</th><th class="px-4 py-3">ارزش</th><th class="px-4 py-3">اقدام</th></tr></thead>
                <tbody class="divide-y divide-gray-100 dark:divide-gray-800">
                    <tr><td class="px-4 py-3 tabular-nums">OPP-۱۰۰۱</td><td class="px-4 py-3 font-medium text-gray-900 dark:text-white">تأمین مبلمان اداری</td><td class="px-4 py-3">پارس تجهیز</td><td class="px-4 py-3"><x-filament::badge color="info">پیشنهاد</x-filament::badge></td><td class="px-4 py-3 tabular-nums">۸۵۰ م</td><td class="px-4 py-3"><x-filament::button size="xs" color="gray" disabled>مشاهده</x-filament::button></td></tr>
                    <tr><td class="px-4 py-3 tabular-nums">OPP-۱۰۰۲</td><td class="px-4 py-3 font-medium text-gray-900 dark:text-white">خط تولید نوتوپیا</td><td class="px-4 py-3">آریا صنعت</td><td class="px-4 py-3"><x-filament::badge color="success">مذاکره</x-filament::badge></td><td class="px-4 py-3 tabular-nums">۲٫۱ م‌م</td><td class="px-4 py-3"><x-filament::button size="xs" color="gray" disabled>مشاهده</x-filament::button></td></tr>
                </tbody>
            </table></div>
        </div>
        <div class="space-y-3 lg:hidden">
            @foreach ([['تأمین مبلمان اداری','OPP-۱۰۰۱ · پارس تجهیز','۸۵۰ میلیون','info','پیشنهاد'],['خط تولید نوتوپیا','OPP-۱۰۰۲ · آریا صنعت','۲٫۱ میلیارد','success','مذاکره']] as $record)
                <article class="space-y-3 rounded-xl border border-gray-200 bg-white p-4 shadow-sm dark:border-gray-800 dark:bg-gray-900">
                    <div class="flex items-start justify-between gap-3"><div class="min-w-0"><p class="font-semibold text-gray-900 dark:text-white">{{ $record[0] }}</p><p class="mt-1 text-xs text-gray-500">{{ $record[1] }}</p></div><x-filament::badge :color="$record[3]">{{ $record[4] }}</x-filament::badge></div>
                    <div class="flex items-center justify-between border-t border-gray-100 pt-3 text-sm dark:border-gray-800"><span class="text-gray-500">ارزش برآوردی</span><strong class="tabular-nums text-gray-900 dark:text-white">{{ $record[2] }}</strong></div>
                    <div class="flex flex-wrap gap-2"><x-filament::button size="sm" disabled>مشاهده رکورد</x-filament::button><x-filament::button size="sm" color="gray" disabled>اقدامات</x-filament::button></div>
                </article>
            @endforeach
        </div>
    </section>

    <section class="grid grid-cols-1 gap-3 md:grid-cols-2">
        <div class="space-y-3 rounded-xl border border-gray-200 bg-white p-4 dark:border-gray-800 dark:bg-gray-900">
            <h2 class="text-base font-bold text-gray-950 dark:text-white">اعلان‌ها و وضعیت‌ها</h2>
            <x-filament::alert color="success" title="عملیات موفق">نمونه پیام موفقیت‌آمیز.</x-filament::alert><x-filament::alert color="warning" title="نیازمند توجه">نمونه هشدار درباره موعد پیگیری.</x-filament::alert><x-filament::alert color="danger" title="خطا">نمونه پیام خطا و راهنمای اصلاح.</x-filament::alert><x-filament::alert color="info" title="اطلاع‌رسانی">نمونه پیام راهنما.</x-filament::alert>
        </div>
        <div class="space-y-3 rounded-xl border border-gray-200 bg-white p-4 dark:border-gray-800 dark:bg-gray-900">
            <h2 class="text-base font-bold text-gray-950 dark:text-white">جزئیات و عملیات رکورد</h2>
            <div class="flex flex-wrap items-center justify-between gap-2"><span class="font-semibold text-gray-900 dark:text-white">خط تولید نوتوپیا</span><x-filament::badge color="success">در حال مذاکره</x-filament::badge></div>
            <dl class="grid grid-cols-2 gap-3 text-sm">
                <div><dt class="text-xs text-gray-500">مشتری</dt><dd class="mt-1 font-medium text-gray-800 dark:text-gray-200">آریا صنعت</dd></div><div><dt class="text-xs text-gray-500">مالک</dt><dd class="mt-1 font-medium text-gray-800 dark:text-gray-200">کارشناس فروش</dd></div>
                <div><dt class="text-xs text-gray-500">ارزش</dt><dd class="mt-1 font-medium text-gray-800 dark:text-gray-200">۲٫۱ میلیارد</dd></div><div><dt class="text-xs text-gray-500">پیگیری</dt><dd class="mt-1 font-medium text-gray-800 dark:text-gray-200">۱۴۰۵/۰۸/۲۸</dd></div>
            </dl>
            <div class="flex flex-wrap gap-2 border-t border-gray-100 pt-3 dark:border-gray-800"><x-filament::button size="sm" disabled>ثبت تماس</x-filament::button><x-filament::button size="sm" color="gray" disabled>یادداشت</x-filament::button><x-filament::button size="sm" color="danger" disabled>بایگانی</x-filament::button></div>
        </div>
    </section>

    <footer class="rounded-xl border border-dashed border-amber-400 bg-amber-50 p-4 text-sm leading-7 text-amber-900 dark:border-amber-800 dark:bg-amber-950/30 dark:text-amber-200">
        <strong>محدوده این نمونه:</strong> این صفحه فقط مرجع بصری است؛ فرم‌ها و دکمه‌ها عملیات واقعی انجام نمی‌دهند. ناوبری موبایل از افزونه پنل و منوهای مجاز واقعی تأمین می‌شود و در محیط واقعی پنل قابل ارزیابی است. بازطراحی صفحات عملیاتی پس از بررسی و تأیید این نمونه انجام خواهد شد.
    </footer>
</div>
</x-filament-panels::page>
