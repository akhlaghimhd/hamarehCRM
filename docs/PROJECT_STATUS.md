# myoffice / Notopia — وضعیت پروژه

آخرین به‌روزرسانی: **2026-11-09**  
نسخه وضعیت: **1.1.0**  
قرارداد دامنه: `docs/CATALOG_ARCHITECTURE.md` **v1.0.0**

## استک و محیط
- مسیر لوکال: `~/projects/myoffice` (WSL)
- پنل: `http://localhost:8088/admin`
- Laravel 13 + Filament 4 + MariaDB + فارسی/RTL/Vazirmatn
- کانتینر معمول: `myoffice-app`

## معماری ثابت
- چندشرکتی `company_id`؛ نقش per company؛ Permission/Role؛ Notification به‌جای 404
- دامنه محصول: استخر → خانواده → فصل → مدل → ماتریس مجاز → سفارش/موجودی (جزئیات در CATALOG_ARCHITECTURE)

## فازبندی
| فاز | موضوع | وضعیت |
|-----|--------|--------|
| 0 | Foundation | تقریباً انجام شده |
| 1 | محصول نوتوپیا | **اسپرینت ۱ — ارتقا از جزئی به کامل** |
| 2 | بازاریابی | شروع نشده |
| 3 | فروش/سفارش | شروع نشده (طراحی در معماری ثبت شد) |
| 4 | تولید | شروع نشده |
| 5 | انبار | شروع نشده |
| 6 | مالی | شروع نشده |

## فاز 0 — انجام‌شده
Company, User, Role, Permission, Customer + Resourceها؛ admin / sales تست

## فاز 1 — وضعیت موجود قبل از اسپرینت ۱
- Resourceها: ProductLine/Model/Size/Option، Cover*، Packaging، InsertGuideline، CatalogRule، Notopia*
- TABLE_STANDARDS روی چند List اعمال شده
- فرم مدل: مسیر حذف تیک اقلام و سکشن‌بندی شروع شده
- **نقص شناخته‌شده:** بدون Family/Season رسمی، بدون pivot سفت مدل–SKU/اقلام، تیک‌های قدیمی DB، بدون انتشار مشروط، بدون کپی مدل

## اسپرینت ۱ — چک‌لیست اجرا (همه باید ☑ شوند)
- [x] Migration: `product_families`
- [x] Migration: `catalog_seasons`
- [x] Migration: ستون‌های ProductModel: family_id, season_id, status, internal_note
- [x] Migration: pivot `product_model_size`
- [x] Migration: pivot `product_model_cover_sku`
- [x] Migration: pivot `product_model_option`
- [x] Models + روابط + Scoped company
- [x] Seed Family/Season (Policy همان products.*)
- [x] Filament Resource: ProductFamily, CatalogSeason
- [x] بازنویسی فرم ProductModel: استخر multi-select، بدون includes_*
- [x] اعتبارسنجی status=published فقط با حداقل سایز و CoverSku
- [x] لیست مدل: ستون خانواده، فصل، status؛ TABLE_STANDARDS
- [x] پاک‌سازی UX تیک اقلام؛ mutate ذخیره false روی فلگ‌های قدیمی

## اسپرینت ۲ — بعدی
کپی مدل، ماتریس مدل×رنگ، پیش‌نمایش ترکیب

## معوق وابسته به فاز بعد
- قفل is_active روی سفارش باز (فاز ۳)
- رزرو/کسر موجودی (فاز ۳/۵)
- snapshot قیمت فاکتور (فاز ۳/۶)

## نباید
- Filament 3/Tablar؛ Resource تکراری؛ بدون company_id
- paginated کلید فارسی؛ strtr به‌جای Fa
- پذیرفتن دائمی وضعیت «جزئی/خیر» برای سناریوهای CATALOG_ARCHITECTURE §6

## مسیر بلافاصله
اجرای اسپرینت ۱ از روی §7 معماری؛ پس از اتمام، نسخه وضعیت → 1.2.0
