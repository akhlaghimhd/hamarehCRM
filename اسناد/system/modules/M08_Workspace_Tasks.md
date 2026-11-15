# M08 — Workspace Tasks

| فیلد | مقدار |
|------|--------|
| کد | M08 |
| اولویت | P1 |
| اسکواد | S5 |
| وابستگی | M01 |

## هدف
کار و ارجاع تیمی؛ وابسته یا مستقل از سفارش/فرصت.

## محدوده
- Task: عنوان، توضیح، assignee، reporter، priority، due_at، status
- وضعیت: open، in_progress، done، cancelled
- لینک اختیاری polymorphic: Customer | Opportunity | Order | WorkPackage
- فیلتر: سررسید گذشته، مال من، باز، زیرمجموعه‌های مجاز
- از UI موجودیت والد بتوان Task ساخت
- مدیر فقط با مجوز team.tasks.view فعالیت و Task زیرمجموعه مستقیم و غیرمستقیم را مشاهده کند.
- عملیات واگذاری، پیگیری، تأیید، رد یا اصلاح نیازمند مجوز مستقل و Audit است.

## کارهای اجرایی
1. مهاجرت tasks
2. Resource Task
3. Actions ایجاد از روی Opportunity/Order
4. مجوز view/create/update و مجوزهای تیمی مستقل
5. هماهنگی با M11 برای یادآوری سررسید
6. اتصال فیلترهای مدیریتی به سلسله‌مراتب عضویت M01

## DoD
- [ ] Task مستقل قابل ایجاد است
- [ ] Task وابسته به سفارش/فرصت نمایش داده می‌شود
- [ ] ارجاع به کاربر دیگر همان شرکت
- [ ] لیست «کارهای من»
- [ ] مدیر فقط محدوده تیم مجاز خود را مشاهده و مدیریت می‌کند

## قواعد
Task ابزار ارجاع انسانی است و جایگزین Workflow Instance یا WorkPackage نیست. Task می‌تواند توسط Transition یک Workflow ایجاد شود؛ تکمیل Task به‌تنهایی وضعیت سفارش/تولید را تغییر نمی‌دهد مگر Transition مجاز آن را انجام دهد.

- Task دارای company_id، creator، assignee، due_at، priority، status، subject و تاریخچه باشد.
- ارجاع فقط به کاربر عضو همان شرکت مجاز است.
- Task تکراری ناشی از Job/Transition با idempotency key کنترل شود.
- یادآوری و Escalation از M11/Scheduler انجام شود.
