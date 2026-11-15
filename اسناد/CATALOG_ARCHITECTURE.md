# معماری کاتالوگ و پیکربندی محصول هم‌ره

| فیلد | مقدار |
|---|---|
| نسخه | 2.0.0 |
| تاریخ | 2026-11-14 |
| وضعیت | قرارداد دامنه‌ای در چارچوب موتور عمومی محصول |
| استک | PHP 8.4 + Laravel 13 + Filament 5 + MariaDB |

این سند جزئیات نوتوپیا را در چارچوب موتور عمومی محصول تعریف می‌کند. نوتوپیا اولین نمونه عمیق است؛ ساختار آن به همه ProductTypeها تحمیل نمی‌شود.

## ۱. اصول
1. محصول و ویژگی از داده تعریف می‌شوند؛ محصول/ویژگی جدید نباید نیازمند تغییر کد یا Migration باشد.
2. استخر امکانات یک‌بار تعریف و با رابطه مجاز به نوع/مدل وصل می‌شود.
3. Rule شرطی اعتبار ترکیب را تعیین می‌کند؛ تیک ثابت includes_* ممنوع است.
4. قیمت از اجزای قابل ردیابی و Rule نسخه‌دار محاسبه می‌شود.
5. Variant فقط با نیاز SKU/موجودی/قیمت/تولید مستقل ایجاد می‌شود.
6. سفارش تأییدشده Snapshot تغییرناپذیر از پیکربندی، قیمت، Rule و نسخه تعریف دارد.
7. داده دامنه company-scoped است.

## ۲. مدل مفهومی
ProductType → (اختیاری) ProductLine → ProductFamily → CatalogSeason → ProductModel → Attribute Schema + Option Pool → Allowed Matrix + Catalog Rules → Configurator → QuoteLine/OrderLine Snapshot.

ProductType رکورد داده‌ای با کد پایدار، عنوان، قابلیت‌ها و تنظیمات است؛ کد نباید مبنای شرط اختصاصی PHP باشد.

### نوتوپیا
ProductLine → ProductFamily → CatalogSeason → ProductModel؛ هر مدل می‌تواند Size، CoverSku، ProductOption و Packaging مجاز داشته باشد. این لایه‌ها برای کاتالوگ نوتوپیا تعریف شده‌اند. این ساختار برای نوتوپیا است؛ انواع دیگر می‌توانند مدل ساده یا ساختار متفاوت داشته باشند.

### Attribute
Attribute شامل code، نام فارسی، data_type، واحد، required، default، validation، منبع گزینه، ترتیب و تنظیمات نمایش است. انواع پایه: text، integer، decimal، boolean، date، single_select، multi_select، measurement و reference.

### Option Pool و ماتریس مجاز
ProductOption استخر اجزا/خدمات/امکانات است. Pivot مجاز می‌تواند min/max quantity، default، recommended، sort و effective dates داشته باشد. Rule مکمل ماتریس پایه است.

## ۳. Rule Engine
Rule JSON ساخت‌یافته و نسخه‌دار است: scope، condition، action، priority، effective dates، پیام فارسی و تست نمونه.
- Validation: رد انتخاب نامعتبر
- Requirement: الزام انتخاب
- Exclusion: منع هم‌زمانی
- Visibility: نمایش شرطی
- Quantity: حد تعداد
- Calculation: تعدیل قیمت/مقدار
- Routing: انتخاب قالب تولید

هیچ Rule حق اجرای PHP/SQL/Expression دلخواه ندارد. فقط عملگرها و Actionهای مجاز و deterministic اجرا می‌شوند.

## ۴. قیمت
قیمت می‌تواند از base price، SKU/Option، مقدار، مشتری/کانال، تاریخ اثر، تخفیف و هزینه‌های مصوب تشکیل شود. خروجی Price Breakdown اجزای محاسبه را نشان می‌دهد. واحد پول مرجع طبق سناریوهای قبلی ریال (IRR) است؛ دقت ذخیره‌سازی و نحوه نمایش ریال/تومان باید دقیقاً تثبیت شود. cost_price از sales_price جداست.

## ۵. Variant و SKU
ترکیب ویژگی‌ها لزوماً Variant نیست. Variant زمانی مادی‌سازی می‌شود که موجودی، SKU، قیمت، خرید یا تولید مستقل نیاز باشد. تولید تمام ترکیب Cartesian ممنوع است.

## ۶. انتشار و نسخه‌بندی
- چرخه draft → validated → published → superseded/archived.
- انتشار Revision immutable می‌سازد.
- Quote به Revision مشخص متصل است.
- OrderLine علاوه بر ارجاع، Snapshot مستقل دارد.
- تغییر تعریف منتشرشده با نسخه جدید انجام می‌شود.
- کپی مدل، Attribute/Option/Rule را فقط در محدوده همان شرکت کپی می‌کند.

## ۷. قرارداد سرویس
```
CatalogConfigurator::schema(companyId, definitionRevision)
CatalogConfigurator::validate(companyId, definitionRevision, payload)
CatalogConfigurator::price(companyId, definitionRevision, payload, context)
CatalogConfigurator::snapshot(companyId, definitionRevision, payload)
```
سرویس مستقل از Filament است تا API، Import و UI یک اعتبارسنجی واحد داشته باشند.

## ۸. سناریوهای پذیرش
- ProductType جدید بدون تغییر PHP/Migration
- Attribute جدید و فرم پویا
- Optionهای مجاز برای مدل
- Rule وابستگی و ناسازگاری با پیام فارسی
- محدودیت تعداد و مقدار پیش‌فرض
- Price Breakdown
- عدم تغییر Snapshot پس از انتشار Revision جدید
- اجرای موتور روی نوتوپیا و نوع محصول متفاوت

## ۹. بنچمارک
- ERPNext Item Variant مبتنی بر Template و Attribute: https://docs.frappe.io/erpnext/item-variants
- Salesforce CPQ Ruleهای Validation/Exclusion/Requirement و Scope: https://trailhead.salesforce.com/content/learn/modules/product-configuration-with-revenue-cloud/use-product-configurator-with-business-rules-engine
- Odoo BoM وابسته به Variant و عملیات: https://www.odoo.com/documentation/master/applications/inventory_and_mrp/manufacturing/basic_setup/bill_configuration.html

این‌ها الگوهای مقایسه‌اند، نه وابستگی یا الزام به کپی کامل.

## ۱۰. حفظ ماتریس پذیرش قبلی A–G

ماتریس سناریوهای سند 1.0.0 حذف یا بی‌اعتبار نشده و همچنان دامنه پذیرش نوتوپیاست:
- A1–A10: خانواده/مدل/سال/کد/استخر/pivot/کپی/پیش‌نمایش/بایگانی
- B1–B6: اقلام، قواعد و Snapshot
- C1–C8: موجودی و گردش آن
- D1–D8: سفارش و فاکتور
- E1–E4: تولید
- F1–F5: UX
- G1–G10: انتشار، بازه فصل، پیش‌فرض، پیشنهادی، یادداشت، حسابرسی، ریسک oversell، پلکان، ریال و تست نقش

این گروه‌بندی باید در چک‌لیست تفصیلی QA حفظ شود؛ این نسخه معماری فقط قرارداد داده‌محور و نسخه‌بندی را به آن اضافه می‌کند.

## ۱۱. تاریخچه
| نسخه | تاریخ | شرح |
|---|---|---|
| 1.0.0 | 2026-11-09 | استخر، خانواده/فصل/مدل، ماتریس A–G |
| 2.0.0 | 2026-11-14 | تعمیم به موتور عمومی ProductType/Attribute/Rule و نسخه‌بندی |
