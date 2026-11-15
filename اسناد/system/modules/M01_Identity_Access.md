# M01 — Identity & Access

| فیلد | مقدار |
|------|--------|
| کد | M01 |
| اولویت | P0 |
| اسکواد | S0 |
| وابستگی | M00 |

## هدف
کاربران، نقش per company، مجوز دانه‌ای، ورود به پنل Filament.

## محدوده
- User (username، فعال/غیرفعال)
- عضویت چند شرکت با role_id و is_default
- Role، Permission، همگام‌سازی Seed
- `hasPermission` / admin bypass همان شرکت
- Resource مدیریت کاربران و نقش‌ها (admin)
- Policy یا ChecksResourcePermission روی Resourceها

## کارهای اجرایی
1. تکمیل مدل User و روابط companies
2. Seeder مجوزهای M04–M11 (نام‌های پایدار)
3. UI: لیست کاربران، تخصیص شرکت/نقش
4. UI: نقش و تیک مجوزها
5. تست دستی: sales شرکت A به داده B دسترسی ندارد
6. کاربر غیرفعال نتواند وارد پنل شود

## مجوزهای پایه (نمونه)
- `customers.view|create|update`
- `opportunities.view|create|update`
- `orders.view|create|update`
- `invoices.view|create`
- `tasks.view|create|update`
- `catalog.manage`
- `users.manage`

## DoD
- [ ] Seed حداقل admin + sales برای دو شرکت
- [ ] نقش sales مجوز مالی مخرب ندارد
- [ ] منوی Filament بر اساس مجوز فیلتر می‌شود
- [ ] سناریوی عدم نشت بین شرکت در SQ ثبت شده



## تکمیل مجوزها برای موتورهای پویا
- مجوزهای تعریف/انتشار Product Definition، Attribute، Catalog Rule، Price Rule، Workflow و Routing Template از مجوز اجرای اسناد عملیاتی جدا باشند.
- Transition باید Permission یا Role requirement مشخص داشته باشد؛ مدیر شرکت فقط در محدوده همان شرکت bypass می‌کند.
- کاربر مجاز به تعریف Rule نیست مگر مجوز مدیریت کاتالوگ/فرایند داشته باشد؛ تعریف Rule به معنی اجازه اجرای همه Actionها نیست.
- Job و API با همان Policy/Permission و Company Context اجرا شوند؛ مجوز UI به‌تنهایی کافی نیست.
