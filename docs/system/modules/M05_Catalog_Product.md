# M05 — Catalog / Product

| فیلد | مقدار |
|------|--------|
| کد | M05 |
| اولویت | P0 |
| اسکواد | S2 |
| وابستگی | M02 |
| مکمل | `docs/CATALOG_ARCHITECTURE.md` |

## هدف
زیرساخت چندماهیتی محصول + کاتالوگ عمیق **نوتوپیا** قابل اعتبارسنجی برای سفارش.

## محدوده
- جدول/مدل **ProductType** (notopia, cheleva, service, …)
- سلسله نوتوپیا: Family، Season، Model
- استخر Size / CoverSku / Option و pivotهای مجاز
- حذف یا خروج از UX تیک‌های includes_* روی مدل
- وضعیت draft/published و `isPublishable`
- سرویس **assertAllowed** برای S3
- قیمت پایه مدل + اجزا برای Quote
- اسکلت خالی/حداقلی ProductType برای cheleva (بدون عمق کامل)

## خارج از محدوده فوری
- پیکربندی کامل چاپ (جعبه/کارت/کاتالوگ متنوع)
- MRP و BOM چندسطحی کارخانه

## کارهای اجرایی
1. ProductType + اتصال به مدل‌های موجود
2. پاکسازی ضدالگوی includes روی UI
3. تکمیل pivot و اعتبار انتشار
4. Resourceهای لازم liman کاتالوگ
5. سرویس مشترک پیکربندی
6. Seed یک مدل نوتوپیا آماده سفارش

## قرارداد برای M06
```
CatalogConfigurator::validate(companyId, productType, modelId, selectionPayload): void
CatalogConfigurator::price(companyId, modelId, selectionPayload): MoneyBreakdown
```

## DoD
- [ ] مدل منتشرشده بدون سایز/جلد publish نمی‌شود
- [ ] ترکیب غیرمجاز از API/سرویس رد می‌شود
- [ ] S3 می‌تواند بدون دست زدن به جزئیات UI کاتالوگ سفارش بسازد
- [ ] ProductType در داده وجود دارد حتی برای انواع غیرفعال عمیق
