# M05 — Catalog / Dynamic Product Platform

| فیلد | مقدار |
|---|---|
| کد | M05 |
| اولویت | P0 |
| اسکواد | S2 |
| وابستگی | M02 |
| اسناد مرجع | ../04_DYNAMIC_PRODUCT_CONFIGURATION.md، ../../CATALOG_ARCHITECTURE.md |

## هدف
موتور عمومی تعریف و پیکربندی محصولات چندماهیتی. نوتوپیا نخستین پیاده‌سازی عمیق این موتور است؛ نه اینکه نوع محصول، ویژگی‌ها و قواعد آن در کد ثابت شوند.

## اصول قطعی
1. ProductType، Family، Season، Model، Attribute، Option و Rule داده‌های قابل مدیریت‌اند.
2. کد ProductType شناسه است، نه switch/case برای رفتار محصول.
3. Family/Season/Model برای محصولاتی که به آن نیاز دارند اختیاری است؛ محصول خدماتی یا ساده مجبور به این سلسله‌مراتب نیست.
4. ویژگی با نوع داده، واحد، اعتبارسنجی، منبع گزینه، ترتیب و قواعد نمایش تعریف می‌شود.
5. ماتریس مجاز و Ruleها ترکیب معتبر را تعیین می‌کنند.
6. فرم پیکربندی از Metadata ساخته می‌شود؛ اعتبارسنجی سمت سرور مرجع نهایی است.
7. Variant فقط در صورت نیاز به SKU، موجودی، قیمت یا تولید مستقل مادی‌سازی می‌شود؛ ساخت همه ترکیب‌های Cartesian ممنوع.
8. تعریف منتشرشده نسخه‌دار است؛ ویرایش با Draft/Revision جدید انجام می‌شود.
9. سفارش Snapshot کامل و نسخه‌دار از تعریف و انتخاب‌ها دریافت می‌کند.

## مفاهیم داده‌ای پیشنهادی
- ProductType: کد، نام، قابلیت‌ها و سیاست‌ها
- ProductLine / ProductFamily / CatalogSeason / ProductModel: لایه‌های اختیاری تجاری؛ در نوتوپیا سلسله‌مراتب موجود حفظ می‌شود
- AttributeGroup / ProductAttribute: نوع داده، واحد، ترجمه، الزام، حدود و اعتبارسنجی
- AttributeOption: گزینه‌های مجاز
- ProductTypeAttribute / ProductModelAttribute: اتصال Attribute با تنظیمات اختصاصی
- ProductOption و pivotهای مجاز: استخر اجزا/خدمات و ماتریس مجاز
- ProductVariant: فقط ترکیب نیازمند هویت/SKU مستقل
- CatalogRule: قواعد نسخه‌دار
- PriceList / PriceRule: قیمت تاریخ‌دار و زمینه‌مند
- ProductDefinitionRevision: نسخه انتشار

این فهرست قرارداد مفهومی است؛ نام و نرمال‌سازی جدول‌ها پس از تطبیق با Schema و Migrationهای واقعی تعیین شود.

## Rule Engine امن
Ruleهای پایه: required/default، include/exclude، mutually_exclusive، depends_on، min/max quantity، conditional_visibility، validation/warning/block، calculated_value/price_adjustment و routing_selection.

Rule به‌صورت DSL محدود و JSON Schema نسخه‌دار ذخیره می‌شود: scope، condition، action، priority، effective dates، پیام فارسی و test cases. فقط عملگرها و Actionهای allowlist شده اجرا می‌شوند؛ eval، PHP یا SQL دلخواه ممنوع است. ارزیابی باید deterministic و قابل توضیح باشد.

## قرارداد برای M06
```
CatalogConfigurator::schema(companyId, definitionRevision)
CatalogConfigurator::validate(companyId, definitionRevision, selectionPayload)
CatalogConfigurator::price(companyId, definitionRevision, selectionPayload, context)
CatalogConfigurator::snapshot(companyId, definitionRevision, selectionPayload)
```

## انتشار
- انتشار فقط پس از اعتبارسنجی Attributeها، Ruleها و ماتریس مجاز.
- تغییر تعریف منتشرشده با Revision جدید.
- سفارش قدیمی از Snapshot خود استفاده می‌کند.
- کپی مدل، Attribute، Option، Rule و اتصال مسیر تولید را با شناسه مقصد در همان شرکت کپی می‌کند.

## DoD
- [ ] نوع محصول جدید فقط با داده تعریف می‌شود.
- [ ] افزودن Attribute جدید بدون تغییر کد و Migration ممکن است.
- [ ] ترکیب نامعتبر در UI و سرور رد می‌شود.
- [ ] قواعد شرطی و ناسازگاری قابل تعریف و تست‌اند.
- [ ] Price Breakdown قابل توضیح است.
- [ ] Snapshot با تغییر کاتالوگ عوض نمی‌شود.
- [ ] نوتوپیا و یک نوع محصول متفاوت از موتور یکسان استفاده می‌کنند.
