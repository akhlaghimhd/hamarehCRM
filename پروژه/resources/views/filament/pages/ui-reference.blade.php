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
            <div role="status" class="rounded-xl border border-emerald-200 bg-emerald-50 p-3 text-sm text-emerald-900 dark:border-emerald-900 dark:bg-emerald-950/40 dark:text-emerald-200"><p class="font-semibold">عملیات موفق</p><p class="mt-1 leading-6">نمونه پیام موفقیت‌آمیز.</p></div>
            <div role="status" class="rounded-xl border border-amber-200 bg-amber-50 p-3 text-sm text-amber-900 dark:border-amber-900 dark:bg-amber-950/40 dark:text-amber-200"><p class="font-semibold">نیازمند توجه</p><p class="mt-1 leading-6">نمونه هشدار درباره موعد پیگیری.</p></div>
            <div role="alert" class="rounded-xl border border-rose-200 bg-rose-50 p-3 text-sm text-rose-900 dark:border-rose-900 dark:bg-rose-950/40 dark:text-rose-200"><p class="font-semibold">خطا</p><p class="mt-1 leading-6">نمونه پیام خطا و راهنمای اصلاح.</p></div>
            <div role="status" class="rounded-xl border border-sky-200 bg-sky-50 p-3 text-sm text-sky-900 dark:border-sky-900 dark:bg-sky-950/40 dark:text-sky-200"><p class="font-semibold">اطلاع‌رسانی</p><p class="mt-1 leading-6">نمونه پیام راهنما.</p></div>
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

    <section
        x-data="{
            rows: [
                { id: 'CO-۱۰۰۸', name: 'پارس تجهیز ایرانیان', national: '۱۰۳۲۰۴۵۶۷۸۹', phone: '۰۲۱-۸۸۷۷۶۶۵۵', email: 'info@pars-tajhiz.ir', status: 'فعال', date: '۱۴۰۵/۰۸/۲۶', amount: ۲۴۵۰۰۰۰۰۰ },
                { id: 'CO-۱۰۰۷', name: 'آریا صنعت نوین', national: '۱۰۱۰۹۸۷۶۵۴۳', phone: '۰۲۱-۴۴۲۲۱۱۰۰', email: 'office@arya-sanat.ir', status: 'فعال', date: '۱۴۰۵/۰۸/۲۴', amount: ۱۸۰۰۰۰۰۰۰ },
                { id: 'CO-۱۰۰۶', name: 'راهکار گستر شرق', national: '۱۴۰۰۱۲۳۴۵۶۷', phone: '۰۵۱-۳۷۶۵۴۳۲۱', email: 'hello@rahkar.ir', status: 'غیرفعال', date: '۱۴۰۵/۰۸/۲۰', amount: ۷۲۰۰۰۰۰۰ },
                { id: 'CO-۱۰۰۵', name: 'توسعه تجارت سپهر', national: '۱۰۳۳۳۳۲۲۲۱۱', phone: '۰۲۱-۸۸۱۱۲۲۳۳', email: 'contact@sepehr.co', status: 'فعال', date: '۱۴۰۵/۰۸/۱۸', amount: ۹۸۰۰۰۰۰۰ },
                { id: 'CO-۱۰۰۴', name: 'فناوران داده‌پرداز', national: '۱۰۱۰۱۰۱۰۱۰۱', phone: '۰۲۶-۳۲۲۲۱۱۰۰', email: 'info@fanavaran.dev', status: 'فعال', date: '۱۴۰۵/۰۸/۱۵', amount: ۵۳۰۰۰۰۰۰ }
            ],
            search: '', status: '', sortKey: 'date', sortAsc: false, page: 1, pageSize: 3,
            visible: { id: true, name: true, national: true, phone: true, email: false, status: true, date: true },
            drawer: false, toast: '', toastType: 'success',
            form: { name: '', slug: '', national: '', phone: '', email: '', address: '' },
            get filtered() {
                const q = this.search.trim().toLocaleLowerCase();
                return this.rows.filter(r => (!this.status || r.status === this.status) &&
                    (!q || Object.values(r).join(' ').toLocaleLowerCase().includes(q)))
                    .sort((a, b) => {
                        const x = a[this.sortKey], y = b[this.sortKey];
                        const result = typeof x === 'number' ? x - y : String(x).localeCompare(String(y), 'fa');
                        return this.sortAsc ? result : -result;
                    });
            },
            get pages() { return Math.max(1, Math.ceil(this.filtered.length / this.pageSize)); },
            get paged() { return this.filtered.slice((this.page - 1) * this.pageSize, this.page * this.pageSize); },
            sort(key) { if (this.sortKey === key) this.sortAsc = !this.sortAsc; else { this.sortKey = key; this.sortAsc = true; } this.page = 1; },
            notify(message, type = 'success') { this.toast = message; this.toastType = type; window.setTimeout(() => this.toast = '', 3200); },
            submit() {
                if (!this.form.name.trim()) return this.notify('نام شرکت را وارد کنید.', 'danger');
                if (this.form.email && !/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(this.form.email)) return this.notify('قالب ایمیل معتبر نیست.', 'danger');
                this.rows.unshift({ id: 'CO-۱۰۰۹', name: this.form.name, national: this.form.national || '—', phone: this.form.phone || '—', email: this.form.email || '—', status: 'فعال', date: '۱۴۰۵/۰۸/۲۷', amount: ۰ });
                this.drawer = false; this.form = { name: '', slug: '', national: '', phone: '', email: '', address: '' }; this.page = 1;
                this.notify('شرکت نمونه با موفقیت ثبت شد.');
            }
        }"
        class="space-y-4"
        dir="rtl"
    >
        <div class="flex flex-wrap items-end justify-between gap-3">
            <div><h2 class="text-base font-bold text-gray-950 dark:text-white">نمونه تعاملی فهرست و مدیریت رکوردها</h2><p class="mt-1 text-xs text-gray-500 dark:text-gray-400">جست‌وجو، فیلتر، مرتب‌سازی، انتخاب ستون، صفحه‌بندی و فرم Drawer را امتحان کنید.</p></div>
            <x-filament::badge color="success">نمونه تعاملی</x-filament::badge>
        </div>

        <div class="grid grid-cols-2 gap-3 xl:grid-cols-4">
            <template x-for="stat in [{label:'همه شرکت‌ها',value:rows.length,icon:'🏢'},{label:'شرکت فعال',value:rows.filter(r => r.status === 'فعال').length,icon:'✓'},{label:'شرکت غیرفعال',value:rows.filter(r => r.status === 'غیرفعال').length,icon:'Ⅱ'},{label:'نتیجه جست‌وجو',value:filtered.length,icon:'⌕'}]" :key="stat.label">
                <article class="rounded-xl border border-gray-200 bg-white p-4 shadow-sm dark:border-gray-800 dark:bg-gray-900">
                    <div class="flex items-center justify-between"><span class="text-xs text-gray-500 dark:text-gray-400" x-text="stat.label"></span><span class="text-lg" x-text="stat.icon"></span></div>
                    <p class="mt-2 text-2xl font-bold tabular-nums text-gray-950 dark:text-white" x-text="stat.value.toLocaleString('fa-IR')"></p>
                </article>
            </template>
        </div>

        <div class="rounded-xl border border-gray-200 bg-white p-3 shadow-sm dark:border-gray-800 dark:bg-gray-900 sm:p-4">
            <div class="flex flex-col gap-3 xl:flex-row xl:items-center xl:justify-between">
                <div class="flex min-w-0 flex-1 flex-col gap-2 sm:flex-row">
                    <label class="relative min-w-0 flex-1"><span class="sr-only">جست‌وجو</span><input x-model.debounce.200ms="search" @input="page=1" type="search" placeholder="جست‌وجو در نام، شناسه، تلفن و ایمیل..." class="w-full rounded-lg border border-gray-300 bg-white px-3 py-2.5 text-sm dark:border-gray-700 dark:bg-gray-950 dark:text-white"></label>
                    <select x-model="status" @change="page=1" class="rounded-lg border border-gray-300 bg-white px-3 py-2.5 text-sm dark:border-gray-700 dark:bg-gray-950 dark:text-white"><option value="">همه وضعیت‌ها</option><option value="فعال">فعال</option><option value="غیرفعال">غیرفعال</option></select>
                </div>
                <div class="flex flex-wrap items-center gap-2">
                    <div class="relative" x-data="{open:false}">
                        <button type="button" @click="open=!open" class="rounded-lg border border-gray-300 px-3 py-2.5 text-sm dark:border-gray-700">انتخاب ستون‌ها ▾</button>
                        <div x-cloak x-show="open" @click.outside="open=false" class="absolute left-0 z-20 mt-2 w-52 rounded-xl border border-gray-200 bg-white p-3 shadow-xl dark:border-gray-700 dark:bg-gray-900">
                            <p class="mb-2 text-xs font-semibold text-gray-500">ستون‌های قابل نمایش</p>
                            <template x-for="(enabled,key) in visible" :key="key"><label class="flex cursor-pointer items-center gap-2 py-1.5 text-sm"><input type="checkbox" x-model="visible[key]" class="rounded border-gray-300 text-teal-600"><span x-text="({id:'شناسه',name:'نام شرکت',national:'شناسه ملی',phone:'تلفن',email:'ایمیل',status:'وضعیت',date:'تاریخ ایجاد'})[key]"></span></label></template>
                        </div>
                    </div>
                    <button type="button" @click="drawer=true" class="rounded-lg bg-teal-600 px-4 py-2.5 text-sm font-semibold text-white shadow-sm hover:bg-teal-700">＋ ایجاد شرکت</button>
                </div>
            </div>

            <div class="mt-4 hidden overflow-x-auto lg:block">
                <table class="w-full min-w-[900px] text-right text-sm">
                    <thead class="border-y border-gray-200 bg-gray-50 text-xs text-gray-600 dark:border-gray-800 dark:bg-gray-800/70 dark:text-gray-300">
                        <tr>
                            <template x-for="col in [{key:'id',label:'شناسه'},{key:'name',label:'نام شرکت'},{key:'national',label:'شناسه ملی'},{key:'phone',label:'تلفن'},{key:'email',label:'ایمیل'},{key:'status',label:'وضعیت'},{key:'date',label:'تاریخ ایجاد'}]" :key="col.key">
                                <th x-show="visible[col.key]" class="whitespace-nowrap px-3 py-3"><button type="button" @click="sort(col.key)" class="inline-flex items-center gap-1 font-semibold hover:text-teal-700"><span x-text="col.label"></span><span x-show="sortKey===col.key" x-text="sortAsc?'↑':'↓'"></span><span x-show="sortKey!==col.key" class="text-gray-300">↕</span></button></th>
                            </template>
                            <th class="px-3 py-3">عملیات</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100 dark:divide-gray-800">
                        <template x-for="row in paged" :key="row.id">
                            <tr class="hover:bg-gray-50/80 dark:hover:bg-gray-800/40">
                                <td x-show="visible.id" class="whitespace-nowrap px-3 py-3 font-mono text-xs" x-text="row.id"></td>
                                <td x-show="visible.name" class="whitespace-nowrap px-3 py-3 font-semibold text-gray-900 dark:text-white" x-text="row.name"></td>
                                <td x-show="visible.national" class="whitespace-nowrap px-3 py-3 tabular-nums" x-text="row.national"></td>
                                <td x-show="visible.phone" class="whitespace-nowrap px-3 py-3 tabular-nums" x-text="row.phone"></td>
                                <td x-show="visible.email" class="whitespace-nowrap px-3 py-3" dir="ltr" x-text="row.email"></td>
                                <td x-show="visible.status" class="whitespace-nowrap px-3 py-3"><span class="rounded-full px-2.5 py-1 text-xs font-medium" :class="row.status==='فعال'?'bg-emerald-50 text-emerald-700 dark:bg-emerald-950 dark:text-emerald-300':'bg-gray-100 text-gray-600 dark:bg-gray-800 dark:text-gray-300'" x-text="row.status"></span></td>
                                <td x-show="visible.date" class="whitespace-nowrap px-3 py-3 tabular-nums" x-text="row.date"></td>
                                <td class="whitespace-nowrap px-3 py-3"><button type="button" @click="notify('این رکورد صرفاً داده نمایشی است.','info')" class="text-xs font-medium text-teal-700 hover:underline dark:text-teal-300">مشاهده</button></td>
                            </tr>
                        </template>
                        <tr x-show="filtered.length===0"><td colspan="8" class="px-4 py-10 text-center text-sm text-gray-500">رکوردی با این مشخصات پیدا نشد.</td></tr>
                    </tbody>
                </table>
            </div>

            <div class="mt-4 space-y-3 lg:hidden">
                <template x-for="row in paged" :key="row.id">
                    <article class="space-y-3 rounded-xl border border-gray-200 p-3 dark:border-gray-700">
                        <div class="flex items-start justify-between gap-3"><div class="min-w-0"><p class="font-semibold text-gray-900 dark:text-white" x-text="row.name"></p><p class="mt-1 text-xs text-gray-500" x-text="row.id"></p></div><span class="shrink-0 rounded-full px-2.5 py-1 text-xs" :class="row.status==='فعال'?'bg-emerald-50 text-emerald-700':'bg-gray-100 text-gray-600'" x-text="row.status"></span></div>
                        <dl class="grid grid-cols-2 gap-3 text-xs">
                            <template x-for="key in ['national','phone','email','date']" :key="key"><div x-show="visible[key]"><dt class="text-gray-500" x-text="({national:'شناسه ملی',phone:'تلفن',email:'ایمیل',date:'تاریخ ایجاد'})[key]"></dt><dd class="mt-1 break-words font-medium text-gray-800 dark:text-gray-200" :dir="key==='email'?'ltr':'rtl'" x-text="row[key]"></dd></div></template>
                        </dl>
                        <button type="button" @click="notify('این رکورد صرفاً داده نمایشی است.','info')" class="w-full rounded-lg border border-gray-200 py-2 text-xs font-medium dark:border-gray-700">مشاهده جزئیات</button>
                    </article>
                </template>
                <div x-show="filtered.length===0" class="rounded-xl border border-dashed border-gray-300 p-8 text-center text-sm text-gray-500">رکوردی برای نمایش وجود ندارد.</div>
            </div>

            <div class="mt-4 flex flex-col gap-3 border-t border-gray-100 pt-3 text-xs text-gray-500 dark:border-gray-800 sm:flex-row sm:items-center sm:justify-between">
                <div class="flex items-center gap-2"><span>تعداد در صفحه</span><select x-model.number="pageSize" @change="page=1" class="rounded-lg border border-gray-300 bg-white px-2 py-1.5 dark:border-gray-700 dark:bg-gray-950"><option value="3">۳</option><option value="5">۵</option><option value="10">۱۰</option></select><span x-text="filtered.length.toLocaleString('fa-IR')+' رکورد'"></span></div>
                <div class="flex items-center justify-between gap-3 sm:justify-end"><span x-text="('صفحه '+page+' از '+pages).replace(/[0-9]/g, d => '۰۱۲۳۴۵۶۷۸۹'[d])"></span><div class="flex gap-1"><button type="button" @click="page=Math.max(1,page-1)" :disabled="page===1" class="rounded-lg border border-gray-300 px-3 py-1.5 disabled:opacity-40 dark:border-gray-700">قبلی</button><button type="button" @click="page=Math.min(pages,page+1)" :disabled="page===pages" class="rounded-lg border border-gray-300 px-3 py-1.5 disabled:opacity-40 dark:border-gray-700">بعدی</button></div></div>
            </div>
        </div>

        <div class="grid grid-cols-1 gap-3 md:grid-cols-2">
            <article class="rounded-xl border border-gray-200 bg-white p-4 dark:border-gray-800 dark:bg-gray-900">
                <h3 class="font-semibold text-gray-900 dark:text-white">جزئیات یک رکورد</h3>
                <p class="mt-1 text-xs text-gray-500">الگوی نمایش مشخصات و عملیات وابسته</p>
                <dl class="mt-4 grid grid-cols-2 gap-4 text-sm"><div><dt class="text-xs text-gray-500">نام شرکت</dt><dd class="mt-1 font-medium">پارس تجهیز ایرانیان</dd></div><div><dt class="text-xs text-gray-500">شناسه ملی</dt><dd class="mt-1 tabular-nums">۱۰۳۲۰۴۵۶۷۸۹</dd></div><div><dt class="text-xs text-gray-500">تلفن</dt><dd class="mt-1 tabular-nums">۰۲۱-۸۸۷۷۶۶۵۵</dd></div><div><dt class="text-xs text-gray-500">وضعیت</dt><dd class="mt-1 text-emerald-700">فعال</dd></div></dl>
                <div class="mt-4 flex flex-wrap gap-2 border-t border-gray-100 pt-3 dark:border-gray-800"><button type="button" @click="notify('عملیات نمونه با موفقیت انجام شد.')" class="rounded-lg bg-teal-600 px-3 py-2 text-xs font-medium text-white">عملیات اصلی</button><button type="button" @click="notify('برای عملیات حساس باید تأیید گرفته شود.','warning')" class="rounded-lg border border-gray-300 px-3 py-2 text-xs dark:border-gray-700">عملیات ثانویه</button><button type="button" @click="notify('نمونه خطا برای نمایش اعلان.','danger')" class="rounded-lg border border-rose-200 px-3 py-2 text-xs text-rose-700 dark:border-rose-900">نمایش خطا</button></div>
            </article>
            <article class="rounded-xl border border-gray-200 bg-white p-4 dark:border-gray-800 dark:bg-gray-900">
                <h3 class="font-semibold text-gray-900 dark:text-white">حالت‌های جدول</h3>
                <p class="mt-1 text-xs text-gray-500">نمایش حالت‌های مختلف بدون اتصال به داده واقعی</p>
                <div class="mt-4 space-y-2"><div class="rounded-lg bg-gray-50 p-3 text-sm dark:bg-gray-800">حالت عادی · داده‌ها با موفقیت بارگذاری شده‌اند.</div><div class="rounded-lg border border-dashed border-gray-300 p-3 text-sm text-gray-500">حالت خالی · هنوز رکوردی ثبت نشده است.</div><div class="rounded-lg bg-amber-50 p-3 text-sm text-amber-800 dark:bg-amber-950/40 dark:text-amber-200">حالت بدون نتیجه · فیلترها را تغییر دهید.</div><div class="rounded-lg bg-rose-50 p-3 text-sm text-rose-800 dark:bg-rose-950/40 dark:text-rose-200">حالت خطا · دریافت اطلاعات ناموفق بود.</div></div>
            </article>
        </div>

        <div x-cloak x-show="drawer" x-transition.opacity class="fixed inset-0 z-[100] flex justify-end bg-gray-950/50" @click.self.prevent.stop>
            <aside x-transition:enter="transition ease-out duration-200" x-transition:enter-start="translate-x-full" x-transition:enter-end="translate-x-0" x-transition:leave="transition ease-in duration-150" x-transition:leave-start="translate-x-0" x-transition:leave-end="translate-x-full" class="flex h-full w-full max-w-xl flex-col bg-white shadow-2xl dark:bg-gray-900" role="dialog" aria-modal="true" aria-labelledby="reference-company-drawer-title">
                <header class="flex items-center justify-between border-b border-gray-200 px-5 py-4 dark:border-gray-800"><div><h3 id="reference-company-drawer-title" class="font-bold text-gray-950 dark:text-white">ایجاد شرکت نمونه</h3><p class="mt-1 text-xs text-gray-500">فرم نمایشی برای بررسی چیدمان و اعتبارسنجی</p></div><span class="rounded-full bg-teal-50 px-2.5 py-1 text-xs text-teal-700 dark:bg-teal-950 dark:text-teal-300">فرم جدید</span></header>
                <form @submit.prevent="submit()" class="flex min-h-0 flex-1 flex-col">
                    <div class="flex-1 space-y-5 overflow-y-auto p-5">
                        <section class="space-y-3"><h4 class="text-sm font-semibold text-gray-800 dark:text-gray-200">اطلاعات اصلی</h4><div class="grid grid-cols-1 gap-3 sm:grid-cols-2">
                            <label class="space-y-1.5 sm:col-span-2"><span class="block text-sm font-medium">نام شرکت <b class="text-rose-600">*</b></span><input x-model="form.name" required maxlength="255" placeholder="نام کامل شرکت" class="w-full rounded-lg border border-gray-300 px-3 py-2.5 text-sm dark:border-gray-700 dark:bg-gray-950"></label>
                            <label class="space-y-1.5"><span class="block text-sm font-medium">شناسه ملی</span><input x-model="form.national" inputmode="numeric" maxlength="11" placeholder="شناسه ملی" class="w-full rounded-lg border border-gray-300 px-3 py-2.5 text-sm dark:border-gray-700 dark:bg-gray-950"></label>
                            <label class="space-y-1.5"><span class="block text-sm font-medium">تلفن</span><input x-model="form.phone" inputmode="tel" placeholder="شماره تماس" class="w-full rounded-lg border border-gray-300 px-3 py-2.5 text-sm dark:border-gray-700 dark:bg-gray-950"></label>
                            <label class="space-y-1.5 sm:col-span-2"><span class="block text-sm font-medium">ایمیل</span><input x-model="form.email" type="email" dir="ltr" inputmode="email" placeholder="name@example.com" class="w-full rounded-lg border border-gray-300 px-3 py-2.5 text-left text-sm dark:border-gray-700 dark:bg-gray-950"></label>
                            <label class="space-y-1.5 sm:col-span-2"><span class="block text-sm font-medium">شناسه سیستمی</span><input x-model="form.slug" dir="ltr" inputmode="url" placeholder="به‌صورت خودکار ایجاد می‌شود" class="w-full rounded-lg border border-gray-300 px-3 py-2.5 text-left text-sm dark:border-gray-700 dark:bg-gray-950"></label>
                            <label class="space-y-1.5 sm:col-span-2"><span class="block text-sm font-medium">نشانی</span><textarea x-model="form.address" rows="3" placeholder="نشانی رسمی شرکت" class="w-full rounded-lg border border-gray-300 px-3 py-2.5 text-sm dark:border-gray-700 dark:bg-gray-950"></textarea></label>
                        </div></section>
                        <p class="rounded-lg bg-sky-50 p-3 text-xs leading-6 text-sky-800 dark:bg-sky-950/40 dark:text-sky-200">این فرم فقط نمونه تعاملی است و اطلاعات آن در سامانه ذخیره نمی‌شود.</p>
                    </div>
                    <footer class="flex justify-end gap-2 border-t border-gray-200 p-4 dark:border-gray-800"><button type="button" @click="drawer=false" class="rounded-lg border border-gray-300 px-4 py-2.5 text-sm dark:border-gray-700">انصراف</button><button type="submit" class="rounded-lg bg-teal-600 px-5 py-2.5 text-sm font-semibold text-white">ثبت نمونه</button></footer>
                </form>
            </aside>
        </div>

        <div x-cloak x-show="toast" x-transition class="fixed bottom-5 left-5 z-[120] max-w-sm rounded-xl px-4 py-3 text-sm font-medium text-white shadow-xl" :class="{'bg-emerald-600':toastType==='success','bg-rose-600':toastType==='danger','bg-sky-600':toastType==='info','bg-amber-600':toastType==='warning'}" role="status" x-text="toast"></div>
    </section>

</div>
</x-filament-panels::page>
