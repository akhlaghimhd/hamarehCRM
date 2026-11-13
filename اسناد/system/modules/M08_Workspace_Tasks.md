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
- فیلتر: سررسید گذشته، مال من، باز
- از UI موجودیت والد بتوان Task ساخت

## کارهای اجرایی
1. مهاجرت tasks
2. Resource Task
3. Actions ایجاد از روی Opportunity/Order
4. مجوز view/create/update
5. هماهنگی با M11 برای یادآوری سررسید

## DoD
- [ ] Task مستقل قابل ایجاد است
- [ ] Task وابسته به سفارش/فرصت نمایش داده می‌شود
- [ ] ارجاع به کاربر دیگر همان شرکت
- [ ] لیست «کارهای من»
