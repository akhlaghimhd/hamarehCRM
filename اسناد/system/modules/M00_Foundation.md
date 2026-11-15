# M00 — Foundation

| فیلد | مقدار |
|------|--------|
| کد | M00 |
| اولویت | P0 |
| اسکواد | S0 |
| وابستگی | — |

## هدف
زیرساخت مشترک همه ماژول‌ها: شرکت جاری، پیوست، شماره‌گذاری، ممیزی حداقلی، تنظیمات.

## محدوده
- Session/کانتکست `current_company_id`
- Trait ایزوله شرکت (سخت‌گیرانه)
- مدل/جدول Attachment عمومی (polymorphic)
- Sequence شماره‌گذاری per company per type
- ثبت created_by/updated_by جایی که لازم
- صفحه یا مکان تنظیمات عمومی شرکت (حداقلی)

## خارج از محدوده
- موتور workflow عمومی پیچیده
- Event bus کامل SaaS

## کارهای اجرایی
1. بازبینی و سخت‌کردن `BelongsToCompany` (بدون fallback خطرناک production)
2. Middleware/Filament hook تضمین شرکت جاری پس از لاگین
3. جدول `attachments`
4. جدول `number_sequences` یا معادل
5. Helper صدور شماره: `Order`, `Quote`, `Invoice`, …
6. Seed تنظیمات اولیه

## مدل‌ها / جداول پیشنهادی
- `attachments`: company_id, attachable_type, attachable_id, path, original_name, mime, size, created_by
- `number_sequences`: company_id, key, prefix, next_number, padding

## مجوزها
- مدیریت تنظیمات شرکت: فقط admin

## DoD
- [ ] بدون company_id رکورد عملیاتی ساخته نمی‌شود
- [ ] تعویض شرکت در UI لیست‌ها را عوض می‌کند
- [ ] پیوست روی حداقل یک موجودیت نمونه کار می‌کند
- [ ] شماره یکتا per company برای یک نوع سند صادر می‌شود



## تکمیل معماری Foundation
- Attachment باید company، سطح دسترسی، disk/path، نام اصلی، MIME واقعی، اندازه، uploader و وضعیت حذف داشته باشد؛ دانلود فقط از مسیر Policyدار.
- Sequence در تراکنش و با قفل اتمیک صادر شود؛ یکتایی در محدوده company + document_type تضمین شود.
- Audit کسب‌وکار از Log فنی جدا باشد و actor، company، subject، action، before/after و زمان را ثبت کند.
- Job عملیاتی company_id صریح داشته باشد؛ Session مبنای اجرای Job نیست.
- تنظیمات شرکت دارای کلید، نوع داده، اعتبارسنجی و scope باشد؛ مقدار حساس رمزگذاری شود.

## DoD تکمیلی
- [ ] فایل شرکت دیگر قابل دانلود نیست.
- [ ] صدور شماره همزمان شماره تکراری نمی‌سازد.
- [ ] Audit عملیات حساس قابل جستجو است.
- [ ] Job بدون Company Context صریح اجرا نمی‌شود.


## اتصال Foundation به شبکه فروش و پورسانت
- شماره‌گذاری per company برای کد نمایندگی، قرارداد، سند پورسانت و تسویه قابل استفاده باشد.
- Audit تغییر وضعیت نماینده، قرارداد، انتساب فروش، نسخه سیاست پورسانت، تأیید و تسویه را ثبت کند.
- Eventهای مالی/فروش شناسه یکتا و idempotency دارند؛ retry نباید سند تکراری بسازد.
- فایل قرارداد و مستندات نماینده از Attachment مجاز و Policyدار استفاده می‌کند.
