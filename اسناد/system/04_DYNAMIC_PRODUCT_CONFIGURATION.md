# معماری موتور عمومی محصول و پیکربندی پویا

| فیلد | مقدار |
|---|---|
| شناسه | SYS-DYNAMIC-PRODUCT |
| نسخه | 1.0.0 |
| تاریخ | 2026-11-14 |
| وضعیت | قرارداد معماری برای M05/M06 |

## ۱. هدف و اصل ضد-hardcode
شرکت باید بتواند انواع محصول، خدمت، ویژگی، گزینه، ترکیب مجاز، قیمت و قواعد پیکربندی را از پنل تعریف کند. افزودن محصول یا ویژگی تجاری نباید مستلزم افزودن کلاس، شرط PHP یا Migration اختصاصی باشد.

کد باید قابلیت عمومی را پیاده کند؛ داده باید محصول و رفتار قابل تنظیم آن را تعریف کند.
- ممنوع: if/switch بر اساس نام محصول یا ProductType برای تعیین مراحل، رنگ، سایز، Option یا فرمول تجاری.
- مجاز: کد پایدار Type Code برای ارجاع؛ رفتار از capability metadata، Attribute Schema، Rule و Template نسخه‌دار خوانده شود.
- قابلیت فنی کاملاً جدید ممکن است توسعه کد بخواهد؛ افزودن مقدار/گزینه تجاری نباید.

## ۲. مدل مفهومی
### ProductType
رکورد داده‌ای متعلق به شرکت: code، name، description، capabilities، configuration mode، inventory/pricing/production policies و active status. Seed اولیه مجاز است، اما ایجاد و مدیریت انواع جدید باید از پنل ممکن باشد.

### Product Definition
ساختار عمومی: ProductType، ProductLine (اختیاری)، ProductFamily (اختیاری)، CatalogSeason (اختیاری)، ProductModel/Definition، Revision، Attribute Schema، Option Pool، Allowed Matrix، Catalog Rules، Price Rules و اتصال Routing Template.
ProductLine/Family/Season/Model لایه‌های اختیاری‌اند؛ ساختار دقیق نوتوپیا حفظ می‌شود، اما همه انواع محصول مجبور به داشتن آن نیستند.

### Attribute Schema
Attribute شامل code، عنوان، data_type، واحد، required/default، min/max/precision، اعتبارسنجی محدود، منبع گزینه، searchable/filterable، scope نمایش و ترتیب است.
نوع‌های پایه: string، integer، decimal، boolean، date، single_select، multi_select، measurement و reference. نوع داده جدید فقط با پشتیبانی فنی صریح افزوده شود.

### Option Pool و ماتریس مجاز
Option می‌تواند جزء، خدمت جانبی، ماده، بسته‌بندی یا قابلیت انتخابی باشد. رابطه مجاز با ProductType/Definition در pivot نگهداری می‌شود. pivot می‌تواند min/max quantity، default، recommended، ترتیب و بازه اعتبار داشته باشد.

### مقدارها و Snapshot
تعریف Attribute رابطه‌ای و قابل مدیریت است. مقدار انتخاب‌شده در payload ساخت‌یافته JSON نگهداری می‌شود و با schema version اعتبارسنجی می‌گردد. برای فیلدهای پرتکرار در گزارش/جستجو، projection یا ستون/جدول typed ساخته شود؛ EAV بی‌قید برای همه داده‌های تراکنشی ممنوع است.

## ۳. Rule DSL امن
Ruleها JSON ساخت‌یافته و نسخه‌دارند:
- شرط‌ها: equals، not_equals، in، not_in، greater_than، less_than، exists، all/any و مقایسه Attribute.
- Actionها: require، exclude، recommend، set_default، show/hide، validate، block، quantity_limit، price_adjustment و select_routing.
- metadata: scope، priority، enabled، effective_from/to، message، test_cases و revision.
- ارزیابی deterministic، مرتب و قابل توضیح.
- فقط عملگرها و Action handlerهای allowlist شده اجرا می‌شوند؛ eval، PHP، SQL و Expression دلخواه ممنوع.
- همان Engine در UI، API، Import و عملیات پس‌زمینه استفاده می‌شود.

## ۴. قیمت‌گذاری
قیمت از اجزای قابل ردیابی تشکیل می‌شود: base price، Variant/SKU، Option و مقدار، تعدیل Rule، قیمت توافقی مشتری/کانال در صورت نیاز، تخفیف، هزینه و مالیات پس از تصویب سیاست مالی.
Price Breakdown باید جزئیات محاسبه را برگرداند. Price Rule نسخه‌دار و تاریخ‌دار است. هنگام تأیید، مبلغ و جزئیات محاسبه Snapshot می‌شود. Cost و Sales Price جدا هستند. واحد پول مرجع طبق سناریوهای قبلی ریال (IRR) است؛ دقت اعشار و نحوه نمایش ریال/تومان باید دقیقاً تثبیت شود.

## ۵. Variant و SKU
Attribute ترکیب‌پذیر الزاماً Variant مستقل نیست. Variant زمانی مادی‌سازی می‌شود که نیاز به SKU، موجودی، بارکد، خرید، قیمت یا تولید مستقل داشته باشد. ترکیب‌ها on-demand ساخته می‌شوند؛ تولید همه ضرب دکارتی ممنوع. SKU در محدوده شرکت یکتا و غیرقابل استفاده مجدد برای کالای دیگر است.

## ۶. انتشار و نسخه‌بندی
چرخه: draft → validated → published → superseded/archived.
- Publish یک Revision immutable می‌سازد.
- هر Quote به Revision مشخص ارجاع می‌دهد.
- OrderLine علاوه بر ارجاع، Snapshot مستقل دارد.
- تغییر تعریف منتشرشده با کپی به Draft جدید انجام می‌شود.
- Rule و Price Rule هم نسخه و تاریخ اثر دارند.
- تغییر ناسازگار با سفارش باز نیازمند هشدار و سیاست migration است.

## ۷. سرویس‌ها
ProductDefinitionService، AttributeSchemaResolver، CatalogRuleEngine، CatalogConfigurator، VariantResolver و PriceCalculator. همه مستقل از Filament هستند؛ UI فقط مصرف‌کننده قرارداد است.

## ۸. چندشرکتی و امنیت
تمام تعریف‌ها، Attributeها، Optionها، Ruleها، PriceListها و Variantها company-scoped هستند، مگر مرجع سراسری با استثناء صریح تصویب شود. هیچ Rule یا reference نباید داده شرکت دیگر را resolve کند.

## ۹. مثال پذیرش
شرکت یک نوع محصول جدید می‌سازد؛ Attributeهای ابعاد، جنس، رنگ و تیراژ را تعریف می‌کند؛ گزینه‌های مواد و روکش را از استخر انتخاب می‌کند؛ Rule ناسازگاری می‌سازد؛ قیمت را از ابعاد/تیراژ/گزینه محاسبه می‌کند و Routing Template را متصل می‌کند. هیچ نام یا Attribute خاصی به PHP اضافه نمی‌شود.

## ۱۰. بنچمارک
- ERPNext از Item Template و Attribute برای Variant استفاده می‌کند؛ هم‌ره از Attribute/Variant اقتباس می‌کند، اما فقط Variantهای دارای نیاز عملیاتی را materialize می‌کند: https://docs.frappe.io/erpnext/item-variants
- Salesforce CPQ از Validation/Exclusion/Requirement Rules و Scopeهای مختلف استفاده می‌کند؛ هم‌ره DSL محدود و قابل تست را اقتباس می‌کند، نه کل پیچیدگی CPQ: https://trailhead.salesforce.com/content/learn/modules/product-configuration-with-revenue-cloud/use-product-configurator-with-business-rules-engine
- Odoo امکان اعمال BOM بر Variant و تعریف عملیات را دارد: https://www.odoo.com/documentation/master/applications/inventory_and_mrp/manufacturing/basic_setup/bill_configuration.html

## ۱۱. خارج از محدوده
فرمول‌نویسی آزاد، اجرای اسکریپت کاربر، configurator سه‌بعدی/CAD، تولید انبوه همه Variantها و CPQ سازمانی پیچیده.

## ۱۲. معیار پذیرش
- ProductType و Attribute جدید بدون تغییر کد/Migration
- دو محصول با ساختار متفاوت از فرم‌ساز عمومی
- رد ترکیب نامعتبر در سرور
- Price Breakdown قابل توضیح
- Snapshot immutable پس از تأیید
- تست جداسازی شرکت و مجوز
