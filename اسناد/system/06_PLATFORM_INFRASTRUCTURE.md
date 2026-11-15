# معماری زیرساخت عملیاتی و قابلیت اطمینان

| فیلد | مقدار |
|---|---|
| شناسه | SYS-PLATFORM |
| نسخه | 1.0.0 |
| تاریخ | 2026-11-14 |
| وضعیت | قرارداد معماری پیشنهادی |

## ۱. هدف
حداقل زیرساخت لازم برای اجرای امن و قابل اتکا. این سند الزام به افزودن سرویس یا پکیج جدید ایجاد نمی‌کند؛ ابتدا امکانات Laravel، MariaDB و Docker موجود استفاده شوند.

## ۲. اجزای زیرساخت
### Database
Migration تنها روش تغییر Schema. تراکنش برای تأیید سفارش، پرداخت، رزرو موجودی و Transition حساس الزامی. ایندکس و Unique در محدوده company_id طراحی شوند.

### Queue و Job
Job عملیاتی company_id صریح داشته باشد؛ Company Context هنگام اجرا resolve و authorize شود، نه از Session. retry/backoff/timeout/failed jobs تعریف شوند. عملیات مالی، شماره‌گذاری، اعلان و موجودی idempotent باشند. اجرای پس از Commit یا Outbox استفاده شود.

### Scheduler
یادآوری سررسید، انقضای Quote، SLA، پاک‌سازی مجاز و گزارش دوره‌ای با Scheduler اجرا شود. اجرای همزمان و تکراری کنترل شود.

### Cache
کلید Cache داده شرکتی شامل company_id و در صورت نیاز user/permission/version باشد. Cache عمومی و حساس تفکیک شوند. تعویض شرکت نباید داده Cache قبلی را نمایش دهد.

### Storage و Attachment
فایل خصوصی در private disk. دانلود از Route کنترل‌شده با Policy. company_id، uploader، MIME واقعی، حجم، checksum و metadata ثبت شود. URL عمومی دائمی یا مسیر قابل حدس ممنوع. اسکن بدافزار در صورت فراهم‌شدن زیرساخت.

### Audit و Logging
Application Log برای خطای فنی؛ Audit Trail برای تغییرات کسب‌وکار؛ Security Log برای ورود/تغییر شرکت/تلاش غیرمجاز. رمز و Token در Log ثبت نشود. retention و دسترسی مشخص باشد.

### Notifications
Event-driven، قابل deduplicate، company-scoped و دارای لینک Policyدار. ارسال بیرونی از Queue. ترجیحات و کانال‌ها قابل توسعه باشند.

### Search و Export
Query و Export همان Scope و Policy لیست را رعایت کنند. Export حجیم به Queue و دارای مالک، شرکت، فیلتر، انقضا و دانلود خصوصی باشد.

### API و Integration
API versioned، Authentication/Authorization، rate limit، idempotency و correlation ID. اتصال مستقیم به hamarehSaasErp فعلاً خارج از محدوده؛ مرز Integration حفظ شود.

### Monitoring و Release
Health check برای DB، Queue و Storage؛ پایش failed jobs، دیسک، خطا و backup. CI حداقل lint/test/migration check و build frontend در محیط مناسب. Release قابل بازگشت؛ Migration ناسازگار نیازمند برنامه expand/contract.

## ۳. امنیت چندشرکتی
company_id در Query، Relation، Policy، Job، Export، File، Cache و Notification enforce شود. شناسه غیرقابل حدس یا مخفی بودن URL مجوز نیست. fallback به اولین شرکت ممنوع.

## ۴. Backup و Restore
Backup پایگاه داده و فایل‌ها با زمان‌بندی/نگهداری مشخص، رمزگذاری و دسترسی محدود؛ محل نگهداری جدا از میزبان اصلی؛ Restore دوره‌ای در محیط آزمایشی. RPO/RTO باید با مالک و ظرفیت زیرساخت تعیین شود؛ عدد فرضی تعیین نمی‌شود.

## ۵. تصمیم‌های باز
مقصد و تناوب Backup، RPO/RTO، سرویس ایمیل/SMS، اسکن فایل، ابزار Monitoring/CI و نیاز جستجوی سراسری.

## ۶. معیار پذیرش
Job بدون Session و با company_id مشخص اجرا شود؛ فایل شرکت دیگر قابل دانلود نباشد؛ Export/Search ایزوله باشد؛ Retry عملیات تکراری نسازد؛ Backup در محیط آزمایشی Restore شود؛ وضعیت failed jobs و health قابل مشاهده باشد.

## ۷. یکپارچگی گردش موجودی

- ثبت رزرو، آزادسازی، انتقال به تولید، مصرف، ورود محصول نهایی، اصلاح و تحویل باید در تراکنش‌های مناسب انجام شود.
- عملیات هم‌زمان روی یک قلم/رزرو باید با قفل‌گذاری یا کنترل هم‌ارز مانع رزرو بیش از موجودی آزاد شوند.
- هر Movement به سند مرجع و شرکت مشخص متصل باشد؛ دفتر گردش (Ledger) مرجع تاریخچه است و مانده/موجودی قابل نمایش، حاصل گردش معتبر یا Projection قابل بازسازی است.
- هر فرمان تغییر موجودی کلید idempotency داشته باشد؛ retry و دوبار کلیک اثر تکراری نسازد.
- انتقال بین محل‌ها مجموع موجودی فیزیکی را تغییر نمی‌دهد؛ مصرف، ضایعات، اصلاح و خروج/تحویل اثر مشخص و قابل ممیزی دارند.
- رویدادهای تولید و انبار از نظر مقدار و واحد سازگار باشند؛ تبدیل واحد فقط با ضریب تعریف‌شده و قابل ردیابی انجام شود.
- خطای میانه عملیات باید rollback یا مسیر جبرانی روشن داشته باشد؛ وضعیت رزرو/تولید نباید نیمه‌ثبت و مبهم باقی بماند.
- گزارش موجودی، جستجو و Export نیز باید company-scoped و مبتنی بر همان دفتر گردش باشند.
