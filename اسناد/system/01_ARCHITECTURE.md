# معماری کلی سیستم عملیاتی هم‌ره

| فیلد | مقدار |
|------|--------|
| شناسه | SYS-ARCH |
| نسخه | **1.0.0** |
| تاریخ | 2026-11-13 |
| وضعیت | الزام‌آور برای مسیر ۱۴روزه و توسعه بعدی |

---

## ۱. هدف سیستم

یک **سیستم عملیاتی یکپارچه** برای هلدینگ چندشرکتی که پوشش می‌دهد:

1. مدیریت مشتری و پرسنل با سطوح دسترسی
2. بازاریابی (فرصت، قیف، فعالیت)
3. کاتالوگ چندماهیتی با تمرکز عمیق نوتوپیا
4. پیش‌فاکتور و سفارش با پیکربندی و snapshot
5. هاب تولید برون‌سپاری (ارجاع به کارگاه/پیمانکار)
6. کار و ارجاع مستقل یا وابسته به سفارش/فرصت
7. مالی عملیاتی (پیش‌فاکتور، فاکتور، تسویه)
8. موجودی سبک اقلام مرتبط

این سیستم **CRM خالص** یا **ERP کارگاهی کامل** نیست؛ ترکیب **CRM + CPQ سبک + Order-to-Outsource + Finance light** است.

---

## ۲. تصمیم‌های قفل معماری

| تصمیم | انتخاب | دلیل |
|--------|--------|------|
| استقرار مسیر فوری | Monolith Laravel 13 + Filament 4 + MariaDB | سرعت تحویل تیم، UX فارسی آماده |
| ایزوله سازمان | `company_id` + Global Scope | چند شرکت حقوقی؛ ساده‌تر از tenant SaaS برای عملیات داخلی |
| UI ادمین | Filament 4 RTL / Vazirmatn | یک پنل برای همه نقش‌ها |
| ماژولار بودن | Bounded Context منطقی + پوشه‌بندی تدریجی | بدون Modular Monolith سنگین در ۱۴ روز |
| تولید | Outsource Job / Work Package | نقش هاب پیمانکار |
| مالی | Operational only | بدون GL تا دانش/اولویت برسد |
| رابطه با hamarehSaasErp | مرجع بلندمدت؛ خارج از مسیر ۱۴روزه | جلوگیری از دوباره‌کاری و تأخیر |
| زبان | UI و گزارش فارسی؛ Commit انگلیسی | |

---

## ۳. لایه‌های منطقی

```
┌─────────────────────────────────────────────────────────────┐
│  Presentation: Filament Resources / Pages / Widgets           │
├─────────────────────────────────────────────────────────────┤
│  Application: Actions, Policies, Workflow transitions         │
├─────────────────────────────────────────────────────────────┤
│  Domain modules (logical):                                    │
│  Foundation │ Identity │ Org │ Party │ Marketing │ Catalog    │
│  Sales │ Outsource │ Workspace │ Finance │ Inventory │ Report │
├─────────────────────────────────────────────────────────────┤
│  Infrastructure: Eloquent, DB, Files, Queue, Mail, Session    │
└─────────────────────────────────────────────────────────────┘
```

در فاز ۱۴روزه اجبار به پوشه `Domain/Application/Infrastructure` جدا **نیست**؛ اجبار به **مرز مسئولیت و عدم نشت company** هست.

---

## ۴. نقشه ماژول‌ها (از زیرساخت تا انتها)

| کد | ماژول | مسئولیت خلاصه |
|----|--------|----------------|
| **M00** | Foundation | Session شرکت، Sequence، Attachment، Audit پایه، تنظیمات |
| **M01** | Identity & Access | User، Role، Permission، عضویت شرکت، Policy |
| **M02** | Organization | Company، انتخاب شرکت جاری، محدودیت داده |
| **M03** | Party / Customer | مشتری حقیقی/حقوقی، تماس، مالک حساب |
| **M04** | Marketing CRM | Lead (اختیاری)، Opportunity، Stage، Activity |
| **M05** | Catalog / Product | ProductType، کاتالوگ نوتوپیا، آپشن، قوانین مجاز |
| **M06** | Sales Quote & Order | Quote، Order، خطوط با snapshot پیکربندی/قیمت |
| **M07** | Outsource Production | WorkPackage، پیمانکار، وضعیت مراحل، پیوست |
| **M08** | Workspace Tasks | Task عمومی، ارجاع، سررسید؛ لینک به موجودیت‌ها |
| **M09** | Finance Operational | پیش‌فاکتور، فاکتور، پرداخت/تسویه، مانده |
| **M10** | Inventory Light | موجودی SKU/اقلام کلیدی، حرکت ساده |
| **M11** | Reporting & Notifications | ویجت قیف، یادآوری، اعلان درون‌برنامه‌ای |

جریان ارزش اصلی:

```
Marketing (M04) → Quote/Order (M06) → Outsource (M07)
                      ↓                    ↓
                 Finance (M09)      Inventory (M10)
                      ↑
            Party (M03) + Tasks (M08)
```

---

## ۵. مدل داده مفهومی (سطح بالا)

### ۵.۱ موجودیت‌های محوری

- **Company** — مرز داده
- **User** ↔ **Company** (pivot: role_id, is_default)
- **Customer** + **CustomerContact**
- **Opportunity** + **OpportunityStage** + **Activity**
- **ProductType** — notopia | cheleva | service | …
- **ProductFamily / CatalogSeason / ProductModel** (نوتوپیا)
- **ProductOption / CoverSku / ProductSize** + pivotهای مجاز
- **Quote** + **QuoteLine** (config snapshot JSON + price)
- **Order** + **OrderLine** (snapshot قفل‌شده)
- **WorkPackage** (order_id، vendor، stage، due_at)
- **Task** (polymorphic subject اختیاری)
- **Invoice** + **Payment**
- **StockItem** + **StockMovement** (سبک)

### ۵.۲ اصول داده

- هر جدول عملیاتی: `company_id` NOT NULL (جز جداول سیستم سراسری صریح)
- حذف عملیاتی: SoftDeletes جایی که بازیابی معنا دارد
- قیمت و پیکربندی روی سفارش: **snapshot** — تغییر کاتالوگ بعدی سفارش قدیم را عوض نکند
- بین Contextها ترجیح UUID/id منطقی؛ از FKهای درهم‌تنیده غیرضروری بین ماژول‌های دور پرهیز

جزئیات سخت‌گیرانه: `02_RULES_AND_GOVERNANCE.md`

---

## ۶. امنیت و دسترسی

```
Request → Auth User → current_company_id → Role(in company) → Permissions
                ↓
         Global Scope company_id
                ↓
         Policy / ChecksResourcePermission (Filament)
```

- عدم دسترسی: Notification فارسی؛ نه 404 خام برای اکشن مجازنداشته
- مدیر شرکت (`admin`): bypass مجوزهای همان شرکت
- داده بین شرکت‌ها هرگز مخلوط نشود

---

## ۷. گردش‌های کلیدی کسب‌وکار

### ۷.۱ بازاریابی
Lead (اختیاری) → Opportunity (مرحله‌ها) → Activityها → برد/باخت → در صورت برد: Quote

### ۷.۲ نوتوپیا (پیکربندی → تحویل)
کاتالوگ منتشر → Quote با گزینه‌های مجاز → تأیید → Order (snapshot) → WorkPackage(ها) به پیمانکار → به‌روزرسانی وضعیت → Invoice/Payment

### ۷.۳ کار مستقل
Task با/بدون لینک به Customer | Opportunity | Order | WorkPackage

### ۷.۴ چلوا / چاپ
همان `ProductType` و Order؛ بدون اجبار تکمیل BOM/کارگاه در فاز اول

---

## ۸. ساختار کد پیشنهادی (تدریجی)

حداقل در ۱۴ روز:

```
app/
  Models/
  Models/Concerns/BelongsToCompany.php
  Filament/Resources/...
  Filament/Pages/...
  Support/Fa.php
  Policies/
```

پس از پایدار شدن، گروه‌بندی پیشنهادی:

```
app/Domain/Catalog/...
app/Domain/Sales/...
app/Filament/Resources/Marketing/...
```

اجباری نیست اگر زمان را می‌سوزاند؛ مرز منطقی در سند ماژول کافی است.

---

## ۹. یکپارچگی و خارج از محدوده مسیر فوری

**داخل محدوده ۱۴روزه:** همه ماژول‌های M00–M11 با عمق تعریف‌شده در اسناد ماژول (نه نسخه enterprise کامل جهان).

**صریح خارج از محدوده فوری:**
- دفتر کل و حسابداری کامل
- MES / زمان‌سنجی ماشین داخل کارگاه خودی
- پیکربندی کامل چاپ متنوع (جعبه/کارت/…)
- پورتال مشتری عمومی
- مهاجرت کامل به hamarehSaasErp
- موبایل native

جایگاه زیرساختی برای موارد بعدی در M05/M07留 می‌شود.

---

## ۱۰. کیفیت و پذیرش

هر ماژول فقط وقتی Done است که بندهای DoD سند خودش سبز باشد، از جمله:
- ایزوله company در لیست/ایجاد/ویرایش
- مجوز
- داده نمونه فارسی قابل دمو
- مسیر اصلی کاربر بدون بن‌بست

---

## ۱۱. تاریخچه

| نسخه | تاریخ | شرح |
|------|--------|------|
| 1.0.0 | 2026-11-13 | نسخه اول یکپارچه مسیر عملیاتی + ۱۴روزه |
