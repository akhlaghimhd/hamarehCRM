# hamarehCRM — زمینه پروژه

| فیلد | مقدار |
|---|---|
| مخزن | akhlaghimhd/hamarehCRM |
| شاخه توسعه | feature/filament5-upgrade |
| پنل محلی اعلام‌شده | http://localhost:8088/admin |
| خط مبنای تأییدشده 2026-11-14 | PHP 8.4.27، Laravel 13.42.0، Filament 5.11.3، Livewire 4.4.8، MariaDB 10.4 |

این سند خلاصه راه‌یابی است؛ وضعیت واقعی کد، Migration و پیشرفت از آخرین Commit همین شاخه بررسی می‌شود.

## معماری
- سیستم عملیاتی یکپارچه چندشرکتی؛ جداسازی با company_id.
- ماژول‌های M00 تا M11 طبق [معماری کلان](./system/01_ARCHITECTURE.md).
- موتور محصول داده‌محور: [04_DYNAMIC_PRODUCT_CONFIGURATION](./system/04_DYNAMIC_PRODUCT_CONFIGURATION.md).
- Workflow، سفارش و تولید داده‌محور: [05_WORKFLOW_ORDER_PRODUCTION](./system/05_WORKFLOW_ORDER_PRODUCTION.md).
- زیرساخت عملیاتی: [06_PLATFORM_INFRASTRUCTURE](./system/06_PLATFORM_INFRASTRUCTURE.md).

## محصول و کاتالوگ
استخر امکانات، Family/Season/Model، ماتریس مجاز و CatalogRule حفظ می‌شوند. نوتوپیا اولین نمونه موتور عمومی است؛ محصول، Attribute، Option یا Routing جدید نباید به شرط hardcode در PHP نیاز داشته باشد.

## اصول اجرایی
- Laravel 13 + Filament 5 + Livewire 4.
- UI فارسی، RTL، Vazirmatn و اعداد از App\Support\Fa.
- اولویت با قابلیت‌های بومی Filament.
- Migration تنها روش تغییر Schema.
- منطق کسب‌وکار در Action/Service و مستقل از UI.
- تغییرات محلی کاربر بدون اجازه پاک نشوند.
- فقط شاخه feature/filament5-upgrade مبنای توسعه است.

## اسناد مرجع
- [منشور مادر پروژه](./PROJECT-CHARTER.md) — مرجع هدف، اصول و ترتیب توسعه از Foundation
- [AGENT_RULES](./AGENT_RULES.md)
- [فهرست معماری](./system/00_INDEX.md)
- [قواعد حاکمیتی](./system/02_RULES_AND_GOVERNANCE.md)
- [ماژول‌ها](./system/modules/)
- [استاندارد جدول](./TABLE_STANDARDS.md)

## هشدار
PROJECT_STATUS و SPRINT1_SCHEMA گزارش/قراردادهای تاریخی‌اند. تاریخ و وضعیت آن‌ها را قبل از اتکا بررسی کنید؛ تیک قدیمی جای بررسی کد و تست فعلی را نمی‌گیرد.
