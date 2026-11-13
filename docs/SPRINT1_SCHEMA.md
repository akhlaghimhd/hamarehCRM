# اسپرینت ۱ — اسکیمای اجرایی (از CATALOG_ARCHITECTURE 1.0.0)

تاریخ: 2026-11-09  
هدف: خروج از وضعیت جزئی برای A1–A7, B1–B3, F3, G1, G2, G5

## جداول جدید

### product_families
- id, company_id, code string(50), name string(100), description text nullable
- sort int default 0, is_active bool default true, timestamps, softDeletes
- unique(company_id, code)

### catalog_seasons
- id, company_id, code string(50), name string(100), year_label string(20) nullable
- starts_on date nullable, ends_on date nullable, is_active bool default true
- sort int default 0, timestamps, softDeletes, unique(company_id, code)

### product_model_size (pivot)
- id, product_model_id, product_size_id, unique(product_model_id, product_size_id)

### product_model_cover_sku (pivot)
- id, product_model_id, cover_sku_id, unique(product_model_id, cover_sku_id)

### product_model_option (pivot)
- id, product_model_id, product_option_id, is_recommended bool default false
- min_qty int nullable, max_qty int nullable, unique(product_model_id, product_option_id)

## ستون‌های جدید product_models
- product_family_id nullable FK
- catalog_season_id nullable FK
- status string(20) default 'draft' — draft | published | archived
- internal_note text nullable

## منطق انتشار
published فقط اگر: family + season پر، حداقل یک size و یک cover_sku در pivot

## فلگ‌های قدیمی
includes_erasable_pen, includes_cloth در Form نیستند؛ mutate = false

## معیار Done
- خانواده کیوان و مانا؛ فصل 1405 و 1406
- مدل زیبا با ۵ CoverSku و خودکار در pivot
- مدل نسیم با ۲ رنگ بدون خودکار
- publish بدون رنگ → خطا
- فرم بدون تیک دستمال/خودکار
- لیست مدل با خانواده، فصل، status
