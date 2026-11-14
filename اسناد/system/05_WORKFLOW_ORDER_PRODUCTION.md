# معماری گردش‌کار، سفارش و تولید داده‌محور

| فیلد | مقدار |
|---|---|
| شناسه | SYS-WORKFLOW-PRODUCTION |
| نسخه | 1.0.0 |
| تاریخ | 2026-11-14 |
| وضعیت | قرارداد معماری برای M06/M07/M08 |

## ۱. هدف
فرآیند فروش، سفارش، تولید و ارجاع باید قابل تعریف باشند. مراحل نوتوپیا، وضعیت سفارش و نام عملیات پیمانکار نباید در کد ثابت شوند.

## ۲. دو لایه
1. Workflow Definition: وضعیت‌ها، انتقال‌ها، شروط، نقش مجاز، SLA و اقدام‌های مجاز را تعریف می‌کند.
2. Domain Actions: قواعد قطعی مانند اعتبار پیکربندی، قفل Snapshot، پرداخت و رزرو موجودی را با تراکنش اجرا می‌کند.

قابل تنظیم بودن Workflow به معنی قابل دور زدن امنیت، مالکیت شرکت یا یکپارچگی داده نیست.

## ۳. مدل گردش‌کار
مفاهیم پیشنهادی:
- workflow_definitions: code، name، subject type مجاز، company_id و status
- workflow_revisions: version و وضعیت انتشار
- workflow_states: code، name، category معنایی initial/intermediate/success/failure/cancelled
- workflow_transitions: from/to، trigger، condition، permission، input schema و handler مجاز
- workflow_instances: subject، revision، current state، شروع/پایان
- workflow_transition_logs: actor، زمان، from/to، payload و دلیل

Subject Type از فهرست موجودیت‌های مجاز برنامه انتخاب می‌شود؛ polymorphic دلخواه پذیرفته نیست.

## ۴. اجرا و نسخه‌بندی
- تعریف در Draft ساخته و پس از اعتبارسنجی Publish می‌شود.
- هر Instance به Revision آغازین متصل است.
- انتشار نسخه جدید فقط روی Instance جدید اعمال می‌شود.
- مهاجرت Instance قدیمی فقط با عملیات کنترل‌شده، تست‌شده و Auditشده مجاز است.
- Transition: مجوز → بررسی شرکت → guard/validation → lock/transaction → handler مجاز → تغییر State → log/event.
- idempotency key از اجرای تکراری جلوگیری می‌کند.
- پرش وضعیت یا Transition فاقد مجوز رد می‌شود.
- دسته معنایی State برای گزارش مشخص است؛ UI به رنگ یا متن فارسی وابسته نیست.

## ۵. سطح پیچیدگی
فعلاً State Machine/Workflow سبک داده‌محور کافی است؛ موتور BPMN کامل یا سرویس خارجی اضافه نمی‌شود. در صورت نیاز آینده به orchestration پیچیده، ADR جدا برای انتخاب موتور انجام شود.

## ۶. Quote و Order
Stateهای نمونه و قابل Seed:
- Quote: draft، review، sent، accepted، rejected، expired، superseded
- Order: draft، confirmed، in_fulfillment، partially_completed، completed، cancelled

شرکت می‌تواند State/Transitionهای خود را تعریف کند. Invariants ثابت: Quote منقضی یا پیکربندی نامعتبر تأیید نمی‌شود؛ تأیید سفارش Snapshot را قفل می‌کند؛ لغو اثر رزرو/تولید/مالی را با Action جبرانی کنترل می‌کند. در قالب Seed نوتوپیا، چرخه draft → reserved → confirmed → in_production → shipped → cancelled حفظ می‌شود؛ این نام‌ها قابل تنظیم‌اند و Actionهای رزرو/آزادسازی/کسر به Transitionهای معنایی متصل می‌شوند.

Quote Revision و Change Order:
- هر نسخه Quote قابل ردیابی است.
- اصلاح پس از تأیید به Change Order/Revision منجر می‌شود.
- نسخه اصلی و تغییرات بعدی قابل مشاهده‌اند.
- تأیید و تبدیل به Order اتمیک و idempotent است.

## ۷. موتور مسیر تولید
### تعاریف
- OperationDefinition: عملیات عمومی، شرح، نوع اجرا، ورودی/خروجی، مهارت، QC و زمان تخمینی.
- RoutingTemplate و Revision: مسیر عملیات برای ProductType/Definition/Variant یا شرط پیکربندی.
- RoutingStep: عملیات، ترتیب، وابستگی، شرط، execution mode (internal/outsourced/hybrid)، مسئول/مهارت، مدارک و معیار پذیرش.
- ProductionRun: اجرای مسیر برای OrderLine و مقدار.
- WorkPackage: بسته قابل ارجاع برای یک یا چند مرحله.
- Delivery/QualityCheck: تحویل کامل/جزئی، پذیرش/رد، دوباره‌کاری و ضایعات.

### قابلیت‌ها
- مسیر خطی، شاخه‌ای و موازی با گراف وابستگی بدون چرخه
- شرط اجرا بر اساس Attribute/Option/Rule
- اجرای داخلی، پیمانکار یا ترکیبی
- انتخاب پیمانکار بر اساس capability و فعال بودن
- تحویل جزئی و چندباره
- کنترل کیفیت، رد، اصلاح و rework
- فایل، نمونه، دستور ساخت و تأیید مشتری
- Snapshot نسخه Routing همراه OrderLine

### نمونه نوتوپیا
مراحل جلد، لت تبلیغاتی، پلاک، آماده‌سازی محتوا و تجمیع Seed قالب نوتوپیا هستند، نه منطق ثابت. محصول دیگر می‌تواند مراحل کاملاً متفاوت داشته باشد.

## ۸. اتصال ماژول‌ها
- M05: پیکربندی معتبر، Price Breakdown و Routing Candidate
- M06: QuoteLine و Snapshot OrderLine
- M07: ProductionRun و WorkPackage از Snapshot و Routing Revision
- M08: ارجاع انسانی عمومی؛ Task جایگزین Workflow یا WorkPackage نیست
- M09: Invoice/Payment از اسناد قطعی
- M10: Ledger از رویدادهای رزرو/مصرف/تحویل
- M11: اعلان/گزارش از رویدادهای ثبت‌شده

## ۹. تراکنش و بازیابی
تأیید Quote/Order، ایجاد Snapshot و ثبت Event در مرز تراکنشی مشخص انجام شود. Notification/Queue پس از Commit یا از Outbox اجرا شود. Handlerها idempotent باشند؛ retry نباید WorkPackage، Payment یا Stock Movement تکراری بسازد. شکست مرحله باید قابل بازیابی و دارای اقدام جبرانی باشد.

## ۱۰. بنچمارک
- ERPNext BOM مواد و عملیات تولید را تعریف و به Work Order/Job Card منتقل می‌کند؛ هم‌ره از جداسازی Template از اجرای واقعی عملیات اقتباس می‌کند، بدون ساخت MES کامل: https://docs.frappe.io/erpnext/bill-of-materials
- Odoo BoM از اجزای Variant-specific و Operations پشتیبانی می‌کند؛ هم‌ره انتخاب عملیات را به نسخه تعریف و پیکربندی سفارش متصل می‌کند: https://www.odoo.com/documentation/master/applications/inventory_and_mrp/manufacturing/basic_setup/bill_configuration.html
- Camunda توصیه می‌کند نسخه تعریف فرایند از Instanceهای در حال اجرا تفکیک شود؛ هم‌ره همین اصل را اعمال می‌کند، بدون نصب Camunda: https://docs.camunda.io/docs/8.9/components/best-practices/operations/versioning-process-definitions/

## ۱۱. خارج از محدوده
BPMN Engine کامل، MES/OEE، APS، MRP چندسطحی، پورتال مستقل پیمانکار و حقوق/دستمزد پیمانکار.

## ۱۲. معیار پذیرش
- Workflow جدید بدون تغییر PHP
- نسخه جدید روی Instance قدیمی اثر ناخواسته ندارد
- Routing جدید بدون تغییر PHP
- مسیر سری، موازی و شرطی
- دو ProductType با Routing متفاوت
- تحویل جزئی، رد و دوباره‌کاری با تاریخچه
- مجوز و company_id در تمام Transitionها
