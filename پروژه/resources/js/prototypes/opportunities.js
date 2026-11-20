import { createApp, ref, computed, onMounted, watch } from 'vue';
import '../../css/prototypes/opportunities.css';

const App = {
    setup() {
        const root = document.getElementById('hm-opportunities');
        const endpoint = root?.dataset.endpoint || '';
        const rows = ref([]);
        const loading = ref(true);
        const error = ref('');
        const search = ref('');
        const status = ref('');
        const page = ref(1);
        const meta = ref({ current_page: 1, last_page: 1, total: '۰', from: '۰', to: '۰' });
        let debounce;

        const statuses = [
            { value: '', label: 'همه وضعیت‌ها' },
            { value: 'active', label: 'فعال' },
            { value: 'nurturing', label: 'در حال پرورش' },
            { value: 'paused', label: 'متوقف موقت' },
            { value: 'won', label: 'موفق' },
            { value: 'lost', label: 'ناموفق' },
        ];

        const queryString = computed(() => {
            const params = new URLSearchParams({ page: String(page.value) });
            if (search.value.trim()) params.set('q', search.value.trim());
            if (status.value) params.set('status', status.value);
            return params.toString();
        });

        async function load() {
            loading.value = true;
            error.value = '';
            try {
                const response = await fetch(`${endpoint}?${queryString.value}`, {
                    headers: { Accept: 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
                    credentials: 'same-origin',
                });
                if (!response.ok) throw new Error(response.status === 403 ? 'دسترسی به فرصت‌ها مجاز نیست.' : 'دریافت اطلاعات با خطا روبه‌رو شد.');
                const payload = await response.json();
                rows.value = payload.data || [];
                meta.value = payload.meta || meta.value;
            } catch (exception) {
                error.value = exception.message || 'خطای ناشناخته';
            } finally {
                loading.value = false;
            }
        }

        watch([search, status], () => {
            page.value = 1;
            clearTimeout(debounce);
            debounce = setTimeout(load, 250);
        });
        watch(page, load);
        onMounted(load);

        function changePage(next) {
            if (next >= 1 && next <= Number(meta.value.last_page)) page.value = next;
        }

        return { rows, loading, error, search, status, page, meta, statuses, changePage };
    },
    template: `
        <main class="hm-shell">
            <header class="hm-topbar">
                <a class="hm-brand" href="/admin" aria-label="بازگشت به هم‌ره">
                    <span class="hm-brand-mark">ه</span>
                    <span><strong>هم‌ره</strong><small>مدیریت ارتباط با مشتری</small></span>
                </a>
                <div class="hm-top-actions">
                    <span class="hm-pilot-tag">نسخه آزمایشی</span>
                    <a class="hm-back" href="/admin">بازگشت به پنل</a>
                </div>
            </header>
            <section class="hm-heading">
                <div><div class="hm-eyebrow">فروش و بازاریابی</div><h1>فرصت‌های فروش</h1><p>نمای تازه برای پیگیری فرصت‌ها؛ مستقل از پنل فعلی</p></div>
                <div class="hm-total"><strong>{{ meta.total }}</strong><span>فرصت ثبت‌شده</span></div>
            </section>
            <section class="hm-toolbar" aria-label="جستجو و فیلتر">
                <label class="hm-search"><span aria-hidden="true">⌕</span><input v-model="search" type="search" placeholder="جستجوی سازمان، مخاطب یا کد فرصت..." /><kbd>جستجو</kbd></label>
                <label class="hm-select-wrap"><span>وضعیت</span><select v-model="status"><option v-for="item in statuses" :key="item.value" :value="item.value">{{ item.label }}</option></select></label>
            </section>
            <div v-if="error" class="hm-message hm-error">{{ error }}</div>
            <div v-else-if="loading && !rows.length" class="hm-message">در حال دریافت فرصت‌ها…</div>
            <div v-else-if="!rows.length" class="hm-message">فرصتی با این مشخصات پیدا نشد.</div>
            <section v-else class="hm-records">
                <div class="hm-table-wrap"><table class="hm-table">
                    <thead><tr><th>کد</th><th>سازمان و مخاطب</th><th>محصول</th><th>وضعیت</th><th>مرحله</th><th>گام بعدی</th><th>مهلت</th><th>مسئول</th></tr></thead>
                    <tbody><tr v-for="row in rows" :key="row.id">
                        <td><span class="hm-code">{{ row.code }}</span></td>
                        <td><strong class="hm-org">{{ row.organization }}</strong><small class="hm-sub">{{ row.contact }} · {{ row.mobile }}</small></td>
                        <td>{{ row.product }}</td><td><span class="hm-status" :class="'is-' + row.status">{{ row.status_label }}</span></td>
                        <td>{{ row.stage }}</td><td class="hm-next">{{ row.next_step }}</td><td class="hm-date">{{ row.next_step_due }}</td><td>{{ row.followup }}</td>
                    </tr></tbody>
                </table></div>
                <div class="hm-mobile-list">
                    <article v-for="row in rows" :key="row.id" class="hm-card">
                        <div class="hm-card-head"><span class="hm-code">{{ row.code }}</span><span class="hm-status" :class="'is-' + row.status">{{ row.status_label }}</span></div>
                        <div class="hm-card-title">{{ row.organization }}</div><div class="hm-card-contact">{{ row.contact }} <span>·</span> {{ row.mobile }}</div>
                        <div class="hm-card-grid">
                            <div><small>مرحله</small><strong>{{ row.stage }}</strong></div><div><small>محصول</small><strong>{{ row.product }}</strong></div>
                            <div class="hm-card-wide"><small>گام بعدی</small><strong>{{ row.next_step }}</strong></div>
                            <div><small>مهلت پیگیری</small><strong>{{ row.next_step_due }}</strong></div><div><small>مسئول پیگیری</small><strong>{{ row.followup }}</strong></div>
                        </div>
                        <details class="hm-details"><summary>اطلاعات بیشتر</summary>
                            <div><span>مالک فرصت</span><strong>{{ row.owner }}</strong></div><div><span>مبلغ تقریبی</span><strong>{{ row.amount }} ریال</strong></div><div><span>عنوان</span><strong>{{ row.title }}</strong></div>
                        </details>
                    </article>
                </div>
            </section>
            <footer class="hm-footer"><span>نمایش {{ meta.from }} تا {{ meta.to }} از {{ meta.total }}</span>
                <div class="hm-pagination"><button :disabled="page <= 1 || loading" @click="changePage(page - 1)">قبلی</button><span>صفحه {{ page }} از {{ meta.last_page }}</span><button :disabled="page >= Number(meta.last_page) || loading" @click="changePage(page + 1)">بعدی</button></div>
            </footer>
            <div v-if="loading && rows.length" class="hm-loading-line"></div>
            <div class="hm-experiment-note">این صفحه صرفاً آزمایشی و فقط‌خواندنی است؛ اطلاعات اصلی در سیستم فعلی باقی می‌ماند.</div>
        </main>
    `,
};

if (document.getElementById('hm-opportunities')) createApp(App).mount('#hm-opportunities');
