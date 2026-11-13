# myoffice — زمینه پروژه برای ادامه کار

مسیر: ~/projects/myoffice (WSL)  
پنل: http://localhost:8088/admin  
استک: Laravel 13 + Filament 4 + MariaDB + فارسی/RTL/وزیرمتن  

**نسخه زمینه:** 1.1.0 — 2026-11-09  

## معماری پایه
- چندشرکتی با `company_id`
- نقش کاربر per company (`company_user`)
- Permission از Seed؛ Role توسط ادمین
- منو/دکمه بر اساس مجوز؛ عدم دسترسی با Notification نه 404
- کار بدون `company_id` ممنوع

## معماری دامنه محصول (الزامی)
سند کامل: `docs/CATALOG_ARCHITECTURE.md` **v1.0.0**

خلاصه تصمیم:
- استخر امکانات → مدل فقط زیرمجموعه مجاز
- خانواده (`ProductFamily`) + کالکشن/سال عرضه (`CatalogSeason`) + `ProductModel`
- اقلام فقط از `ProductOption`؛ تیک ثابت روی مدل ممنوع
- سفارش فقط گزینه‌های مجاز + قوانین + snapshot قیمت
- موجودی روی SKU/variant با رزرو و کسر از سفارش
- هیچ سناریوی ماتریس A–G در وضعیت جزئی/خیر به‌عنوان وضعیت نهایی پذیرفته نیست

## فازبندی
0 Foundation (~انجام)  
1 محصول نوتوپیا — **اسپرینت ۱ کاتالوگ سخت در جریان**  
2 بازاریابی  
3 فروش/سفارش (ویزارد، قوانین، snapshot)  
4 تولید  
5 انبار  
6 مالی  

## ساخته‌شده (فاز ۰ / پایه ۱)
- Company, User(username), Role, Permission, Customer
- ProductLine/Model/Size/Option، Cover*، Packaging، CatalogRule، NotopiaCluster
- استاندارد جدول: `docs/TABLE_STANDARDS.md`
- `App\Support\Fa` (+ dateTime شمسی در مسیر اعمال)

## نباید
- Filament 3 یا Tablar
- Resource تکراری Customers/Companies
- کار بدون company_id
- رها کردن pivot مدل↔SKU/اقلام
- paginated با کلید فارسی؛ strtr پراکنده

## بعد (اجرایی)
1. پیاده‌سازی اسپرینت ۱ طبق CATALOG_ARCHITECTURE §7  
2. سپس اسپرینت ۲ (کپی و ماتریس)  
3. فاز ۳ سفارش هوشمند  
