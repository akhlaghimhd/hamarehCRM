# چک‌لیست آنبوردینگ — انسان و عامل AI

**مخزن:** akhlaghimhd/hamarehCRM  
**شاخه مجاز توسعه:** feature/filament5-upgrade

## الف) دسترسی و شاخه
- [ ] دسترسی GitHub روی مخزن رسمی تأیید شده است.
- [ ] شاخه feature/filament5-upgrade را fetch و checkout کرده‌ام.
- [ ] develop را تغییر نمی‌دهم.
- [ ] main بدون دستور صریح مالک تغییر نمی‌کند.
- [ ] پیش از Pull، وضعیت Working Tree بررسی و تغییرات محلی حفظ می‌شود.

## ب) محیط
- [ ] نسخه PHP/Composer و وابستگی‌ها از فایل‌های پروژه بررسی شده‌اند.
- [ ] دستورات PHP/Artisan/Composer در محیط Docker با سرویس واقعی app اجرا می‌شوند.
- [ ] محل اجرای Node/npm جداگانه بررسی شده است.
- [ ] .env و Secret هرگز commit نمی‌شوند.

## ج) اسناد مرجع
- [ ] اسناد/AGENT_RULES.md
- [ ] اسناد/system/00_INDEX.md
- [ ] اسناد/system/01_ARCHITECTURE.md
- [ ] اسناد/system/02_RULES_AND_GOVERNANCE.md
- [ ] اسناد/system/03_ROADMAP_AND_PRIORITIES.md
- [ ] اسناد/system/04_DYNAMIC_PRODUCT_CONFIGURATION.md
- [ ] اسناد/system/05_WORKFLOW_ORDER_PRODUCTION.md
- [ ] اسناد/system/06_PLATFORM_INFRASTRUCTURE.md
- [ ] سند ماژول مربوطه در اسناد/system/modules/
- [ ] استانداردهای صفحه/جدول در صورت تغییر UI

## د) اصول دامنه
- [ ] company_id در Query، Relation، Policy، Job، File، Cache و Export رعایت می‌شود.
- [ ] نوع محصول، Attribute، Option، Rule و Routing از داده خوانده می‌شوند.
- [ ] افزودن محصول/ویژگی تجاری نیازمند شرط PHP یا Migration نیست.
- [ ] Workflow و Routing نسخه‌دارند.
- [ ] Snapshot سفارش تأییدشده با تغییر تعریف جاری تغییر نمی‌کند.
- [ ] هیچ Rule داده‌ای PHP/SQL دلخواه اجرا نمی‌کند.

## هـ) گردش کار توسعه
- [ ] محدوده تغییر روشن و محدود است.
- [ ] فایل هدف از شاخه مجاز پیش از ویرایش خوانده شده است.
- [ ] تغییرات ثبت‌نشده کاربر حفظ شده‌اند.
- [ ] تست مرتبط اجرا یا علت عدم اجرا ثبت شده است.
- [ ] Commit انگلیسی و Conventional است.
- [ ] فایل‌ها، SHA و تست‌ها گزارش شده‌اند.

## هشدار
از `git reset --hard`، `git clean`، Force Push یا حذف تغییرات محلی برای حل اختلاف استفاده نکنید مگر مالک صریحاً دستور دهد و اثر آن روشن باشد. در صورت اختلاف Local/Remote ابتدا از تغییرات محافظت و اختلاف را گزارش کنید.
