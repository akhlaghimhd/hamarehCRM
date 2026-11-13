# آزمایش تم Kinetics — مسیر امن با امکان برگشت

| فیلد | مقدار |
|------|--------|
| تاریخ | 2026-11-13 |
| برنچ بک‌آپ | `backup/pre-kinetics-20261113` |
| برنچ آزمایش | `trial/kinetics` |
| بسته | `otatechie/filament-kinetics` (MIT، Packagist) |
| هدف | جداول/کنترل فشرده‌تر؛ در صورت نارضایتی برگشت کامل |

## امنیت و محدودیت

- فقط از Packagist با نام دقیق `otatechie/filament-kinetics` نصب شود؛ از zip ناشناس یا fork ناشناخته استفاده نشود.
- پلاگین community است؛ Filament آن را vet نمی‌کند.
- **تداخل محتمل با فارسی/RTL و `filament-fa.css`** — فونت تم را با Vazirmatn و `dir=rtl` نگه می‌داریم.
- نصب فقط روی برنچ `trial/kinetics`؛ تا تأیید شما روی `develop` merge نشود.

## پیش‌نیاز لوکال

- Node/npm برای `npm run build` تم Vite
- Docker app در حال اجرا
- git تمیز یا stash قبل از تعویض برنچ

## ۱) بک‌آپ را محلی هم داشته باشید

```bash
cd ~/projects/myoffice
timeout 40 git fetch origin
git branch -f backup/pre-kinetics-20261113 origin/backup/pre-kinetics-20261113
git checkout -B trial/kinetics origin/trial/kinetics
```

## ۲) نصب بسته (داخل پوشه پروژه Laravel)

```bash
cd ~/projects/myoffice/پروژه

# فقط همین بسته
docker compose exec app composer require otatechie/filament-kinetics --no-interaction

# تم Filament اگر از قبل نیست
docker compose exec app php artisan make:filament-theme --panel=admin --no-interaction || true

docker compose exec app php artisan kinetics:install --no-interaction || true
```

اگر `kinetics:install` پنل را دست زد، فایل
`app/Providers/Filament/AdminPanelProvider.php`
را چک کنید: باید `KineticsPlugin::make()` باشد **و** hookهای RTL + `filament-fa.css` باقی بمانند.

## ۳) فونت فارسی بعد از پلاگین (الزامی)

در `AdminPanelProvider` بعد از plugin:

```php
->plugin(\Otatechie\Kinetics\KineticsPlugin::make())
->font('Vazirmatn') // یا هر فونتی که CSS شما بار می‌کند
```

و CSS فارسی/RTL را حذف نکنید:

- `public/css/filament-fa.css`
- hook `dir=rtl` و `persian-digits`

## ۴) Build فرانت

```bash
cd ~/projects/myoffice/پروژه
npm install
npm run build
# یا اگر فقط داخل کانتینر:
# docker compose exec app npm install && docker compose exec app npm run build

docker compose exec app php artisan filament:assets
docker compose exec app php artisan view:clear
docker compose exec app php artisan cache:clear
```

سپس پنل: http://localhost:8088/admin

## ۵) چک‌لیست قبول/رد

- [ ] RTL درست است
- [ ] فونت فارسی خوانا است
- [ ] قیف فروش (`mf-funnel`) نشکسته
- [ ] تعویض شرکت درست است
- [ ] جداول فرصت/پیش‌فاکتور فشرده‌تر و قابل‌استفاده‌اند
- [ ] موبایل قابل‌قبول است

## ۶) برگشت کامل (اگر راضی نبودید)

```bash
cd ~/projects/myoffice
git fetch origin
git checkout develop
git reset --hard origin/backup/pre-kinetics-20261113
# یا فقط:
git checkout develop
git merge --ff-only origin/develop   # اگر trial هرگز merge نشده

cd پروژه
# حذف بسته اگر روی develop نصب شده بود:
docker compose exec app composer remove otatechie/filament-kinetics --no-interaction || true
rm -rf resources/css/filament  # فقط اگر تم آزمایشی ساختید و روی develop نمی‌خواهید
npm run build || true
docker compose exec app php artisan view:clear
docker compose exec app php artisan cache:clear
```

برنچ `backup/pre-kinetics-20261113` روی remote نقطهٔ امن قبل از هر تغییر Kinetics است.

## ۷) اگر راضی بودید

فقط با دستور صریح شما: PR یا merge `trial/kinetics` → `develop` + commitهای `composer.lock` و تم Vite.
