# نصب فاز زیرساخت (IAM + شرکت + مشتری + Filament)

## پیش‌نیاز
- کد این commit روی `develop`
- Docker لوکال `hamareCRM` در `E:\myProject\hamareCRM\Project`

## ۱) نصب Filament

```powershell
cd E:\myProject\hamareCRM\Project
docker compose exec app composer require filament/filament:"^4.0" -W
docker compose exec app php artisan filament:install --panels
```

هنگام نصب پنل، نام پیش‌فرض `admin` مناسب است.

## ۲) Migrate و Seed

```powershell
docker compose exec app php artisan migrate
docker compose exec app php artisan db:seed
```

کاربر اولیه:
- username: `admin`
- password: `ChangeMe@1405`
- شرکت: هماره

**رمز را بعد از اولین ورود عوض کنید.**

## ۳) اتصال Middleware شرکت

در `bootstrap/app.php` داخل `withMiddleware`:

```php
$middleware->appendToGroup('web', [
    \App\Http\Middleware\SetCurrentCompany::class,
]);
```

و ثبت `AuthServiceProvider` در bootstrap/providers اگر لازم است.

## ۴) Filament — Resourceها

پس از نصب Filament، Resourceهای زیر را با artisan بسازید و فیلدها را مطابق مدل‌ها پر کنید:

```powershell
docker compose exec app php artisan make:filament-resource Customer --generate
docker compose exec app php artisan make:filament-resource User --generate
docker compose exec app php artisan make:filament-resource Role --generate
docker compose exec app php artisan make:filament-resource Company --generate
```

در هر Resource:

- `canViewAny` / `canCreate` و ... را با `$user->hasPermission('...')` محدود کنید
- فرم مشتری: فیلدهای حقیقی/حقوقی را با `visible(fn ...)` بر اساس `type` نمایش دهید
- RelationManager برای `contacts`

ورود Filament را روی `username` تنظیم کنید (مستندات Filament Auth).

## ۵) تست

```powershell
docker compose exec app php artisan test --filter=CompanyIsolationTest
```

## ۶) بررسی دستی
1. ورود با admin
2. ایجاد مشتری حقوقی با تماس
3. ایجاد شرکت دوم و کاربر دوم — عدم مشاهده مشتریان شرکت اول
