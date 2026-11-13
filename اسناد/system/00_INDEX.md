# فهرست اسناد سیستم عملیاتی هم‌ره (hamarehCRM)

| فیلد | مقدار |
|------|--------|
| نسخه | **1.0.0** |
| تاریخ | 2026-11-13 |
| مخزن | `akhlaghimhd/hamarehCRM` |
| برنچ کار | `develop` |
| افق تحویل فشرده | **۱۴ روز تقویمی با تیم موازی** |
| مخاطب | مالک محصول، لید فنی، تمام اعضای تیم توسعه/تست/عملیات |

این پوشه **منبع حقیقت برنامه‌ریزی و معماری** برای ساخت سیستم عملیاتی شرکت است (CRM + سفارش پیکربندی‌پذیر + هاب پیمانکار + مالی سبک).  
کد و رفتار نهایی باید با این اسناد و `docs/AGENT_RULES.md` سازگار باشد.

---

## ۱. نقشه پوشه‌ها

```
docs/system/
├── 00_INDEX.md                          ← همین فایل
├── 01_ARCHITECTURE.md                   ← معماری کلی زیرساخت تا آخرین ماژول
├── 02_RULES_AND_GOVERNANCE.md           ← بایدها / نبایدها (داده، کد، گیت، امنیت)
├── 03_ROADMAP_AND_PRIORITIES.md         ← اولویت، شماره ماژول، برنامه ۱۴روزه موازی
└── modules/
    ├── M00_Foundation.md
    ├── M01_Identity_Access.md
    ├── M02_Organization_Company.md
    ├── M03_Party_Customer.md
    ├── M04_Marketing_CRM.md
    ├── M05_Catalog_Product.md
    ├── M06_Sales_Quote_Order.md
    ├── M07_Outsource_Production.md
    ├── M08_Workspace_Tasks.md
    ├── M09_Finance_Operational.md
    ├── M10_Inventory_Light.md
    └── M11_Reporting_Notifications.md
```

اسناد مکمل قبلی (حذف نشوند):
- `docs/AGENT_RULES.md`
- `docs/CATALOG_ARCHITECTURE.md` (دامنه نوتوپیا — زیرمجموعه M05)
- `docs/TABLE_STANDARDS.md`
- `PROJECT_CONTEXT.md`

---

## ۲. ترتیب خواندن برای اعضای جدید

| ترتیب | فایل | چرا |
|------:|------|-----|
| 1 | `00_INDEX.md` | جهت‌یابی |
| 2 | `03_ROADMAP_AND_PRIORITIES.md` | چه کاری، کی، کدام اسکواد |
| 3 | `01_ARCHITECTURE.md` | تصویر کل سیستم |
| 4 | `02_RULES_AND_GOVERNANCE.md` | خط قرمزها |
| 5 | ماژول اختصاصی اسکواد (`modules/Mxx_*.md`) | کارهای ریز |

---

## ۳. اصول غیرقابل مذاکره (خلاصه)

1. **چندشرکتی از روز اول** با `company_id` — بدون استثناء عملیاتی.
2. **اولویت کسب‌وکار اعلام‌شده:** بازاریابی → سفارش/تولید برون‌سپاری → انبار/مالی سبک.
3. **محصول چندماهیتی:** زیرساخت `ProductType`؛ پیاده‌سازی عمیق اول نوتوپیا؛ چلوا و چاپ فقط اسکلت.
4. **تولید = هاب پیمانکار** نه MES کارگاه داخلی.
5. **مالی = عملیاتی** (پیش‌فاکتور/فاکتور/تسویه)؛ دفتر کل بعداً.
6. **hamarehSaasErp** پلتفرم بلندمدت است؛ مسیر ۱۴روزه روی همین مخزن Filament است.
7. هیچ ماژول بدون **Definition of Done** در سند خودش «تمام» اعلام نمی‌شود.

---

## ۴. اسکوادهای پیشنهادی اجرا (موازی)

| کد اسکواد | تمرکز | اسناد مرجع |
|-----------|--------|-------------|
| S0 | Foundation + IAM + Company | M00, M01, M02, 02_RULES |
| S1 | Marketing CRM | M03, M04 |
| S2 | Catalog / Notopia | M05 + CATALOG_ARCHITECTURE |
| S3 | Quote / Order | M06 |
| S4 | Outsource / Production hub | M07 |
| S5 | Tasks + Notifications | M08, M11 |
| S6 | Finance light + Inventory light | M09, M10 |
| SQ | QA / داده نمونه / پذیرش | همه DoDها |

جزئیات روزبه‌روز: `03_ROADMAP_AND_PRIORITIES.md`.

---

## ۵. کنترل نسخه اسناد

- SemVer اسناد: MAJOR ناسازگار دامنه / MINOR قابلیت / PATCH اصلاح شرح
- هر تغییر معماری یا اولویت → به‌روزرسانی نسخه فایل + یک خط در تاریخچه همان فایل
- پیام Commit اسناد: `docs(system): ...`

---

## ۶. وضعیت این بسته

| سند | وضعیت |
|-----|--------|
| 00_INDEX | فعال |
| 01_ARCHITECTURE | فعال |
| 02_RULES | فعال |
| 03_ROADMAP | فعال |
| modules/M00–M11 | فعال |
