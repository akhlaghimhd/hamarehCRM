# M08 — Workspace Tasks

| فیلد | مقدار |
|------|--------|
| کد | M08 |
| اولویت | P1 |
| اسکواد | S5 |
| وابستگی | M01 |

## هدف
کار و ارجاع تیمی؛ مستقل یا وابسته به موجودیت‌های عملیاتی. Task ابزار ارجاع انسانی است و با Activityهای ثبت‌شده در CRM یکی نیست.

## محدوده
- Task مستقل: عنوان، توضیح، assignee، reporter، priority، due_at، status
- وضعیت: open، in_progress، done، cancelled
- اولویت: low، normal، high، urgent
- لینک اختیاری polymorphic: Customer | Opportunity | Order | WorkPackage
- فیلتر: سررسید گذشته، کارهای من، مسئول، وضعیت و اولویت
- از UI موجودیت والد امکان ایجاد Task وابسته فراهم شود.
- مدیر فقط با مجوز team.tasks.view کارهای زیرمجموعه مستقیم و غیرمستقیم را مشاهده کند.
- ارجاع به اعضای تیم نیازمند team.tasks.assign و مدیریت کار دیگران نیازمند team.tasks.manage است.
- تغییر وضعیت، مسئول، اولویت، شرح و حذف باید Audit شود.

## قواعد داده و امنیت
- هر Task دارای company_id، creator/reporter، assignee، due_at، priority، status، subject و تاریخچه است.
- ارجاع فقط به کاربر فعال عضو همان شرکت مجاز است.
- تمام Queryها، Policyها و Actionها باید company scope را enforce کنند؛ فیلتر UI به‌تنهایی کافی نیست.
- Task تکراری ناشی از Job/Transition با idempotency_key کنترل می‌شود.
- Task مستقل از Activity است؛ Activity فعلی CRM برای تماس، جلسه، یادداشت و پیگیری فرصت باقی می‌ماند.
- Task می‌تواند توسط Transition یک Workflow ایجاد شود؛ تکمیل Task به‌تنهایی وضعیت سفارش/تولید را تغییر نمی‌دهد مگر Transition مجاز آن را انجام دهد.
- Audit شامل actor، رویداد، زمان و before/after است.

## کارهای اجرایی
1. Migration جدول tasks و audit_events
2. اجرای Seeder مستقل `TaskPermissionsSeeder` برای افزودن امن مجوزها و دسترسی پایه نقش فروش
3. مدل، Policy، Action ذخیره و Resource مدیریت Task
4. فیلترهای کارهای من، سررسید گذشته، مسئول، وضعیت و اولویت
5. ایجاد Task از Opportunity/Order و نمایش در موجودیت والد
6. مجوزهای مستقل مشاهده/ایجاد/ویرایش/ارجاع و مجوزهای تیمی
7. هماهنگی با M11 برای یادآوری سررسید
8. نمایش تاریخچه Audit در صفحه جزئیات Task و Activity

## وضعیت پیاده‌سازی
- [x] مدل و Migration پایه Task و Audit
- [x] کنترل عضویت شرکت در ارجاع
- [x] فهرست مدیریتی Task و فیلترهای اصلی
- [x] ثبت رویدادهای ایجاد/ویرایش/حذف Task و Activity
- [x] اتصال UI ایجاد Task از Opportunity/Order
- [x] نمایش کارهای وابسته روی میزکار فرصت
- [x] نمایش Timeline تاریخچه در جزئیات Task *(RelationManager تاریخچه تغییرات)*
- [ ] یادآوری و Escalation از M11

## DoD
- [x] Task مستقل قابل ایجاد است
- [x] Task وابسته به سفارش/فرصت نمایش/ایجاد می‌شود
- [x] ارجاع به کاربر دیگر همان شرکت مجاز و قابل ممیزی است (مدل + SaveTask)
- [x] لیست «کارهای من» و فیلترهای تیم در Resource
- [ ] مدیر فقط محدوده تیم مجاز خود را مشاهده و مدیریت می‌کند (پوشش تست نقش کامل)
- [ ] دسترسی مستقیم URL، Livewire، API و Job نیز Policy دارد
- [x] سناریوی عدم نشت بین شرکت‌ها در تست پوشش داده شده است

> به‌روزرسانی ۱۸ نوامبر ۲۰۲۶ روی `feature/filament5-upgrade`: اکشن مشترک CreateLinkedTaskAction روی میزکار فرصت و صفحه سفارش؛ Timeline Audit روی صفحه ویرایش کار.
