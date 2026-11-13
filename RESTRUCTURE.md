# بازآرایی ریشه: اسناد + پروژه

## چرا هنوز ریشه دوپوشه‌ای کامل نیست؟

- اسناد سیستم از قبل روی GitHub در **`docs/system/`** و **`docs/AGENT_RULES.md`** هستند و از طریق کانکتور قابل خواندن/نوشتن‌اند.
- جابه‌جایی **کل** درخت Laravel (`app/`, `vendor` نه، اما صدها فایل سورس) فقط با API «ایجاد فایل» عملی و ایمن نیست؛ باید **`git mv` + یک commit + push** از ماشینی با SSH شما انجام شود.

## آنچه الان روی remote هست

- `docs/system/**` — معماری، قوانین، نقشه راه، M00–M11
- `docs/AGENT_RULES.md`
- `اسناد/00_AI_AND_HUMAN_INTRODUCTION.md`
- `اسناد/ONBOARDING_CHECKLIST.md`
- `اسناد/README.md`
- این فایل `RESTRUCTURE.md`

## دستور بازآرایی کامل (روی لوکال شما)

```bash
cd ~/projects/myoffice   # یا مسیر کلون
git checkout develop
git pull origin develop

mkdir -p اسناد پروژه

# اسناد موجود
git mv docs اسناد/system
# اگر تو در تو شد اصلاح کن:
# mkdir -p اسناد/system && git mv اسناد/system/system/* اسناد/system/ && rmdir اسناد/system/system 2>/dev/null || true

# بالا آوردن قوانین به ریشه اسناد
for f in AGENT_RULES.md CATALOG_ARCHITECTURE.md PROJECT_STATUS.md SPRINT1_SCHEMA.md TABLE_STANDARDS.md; do
  [ -f "اسناد/system/$f" ] && git mv "اسناد/system/$f" "اسناد/$f"
done

# کد به پروژه
for item in app artisan bootstrap config database lang public resources routes storage tests \
  composer.json composer.lock package.json phpunit.xml vite.config.js Dockerfile compose.yaml \
  .editorconfig .env.example .gitattributes .npmrc; do
  [ -e "$item" ] && git mv "$item" "پروژه/"
done

# فایل‌های مقدمه از قبل در اسناد/ هستند
git add -A
git status
git commit -m "chore: restructure repo into اسناد/ and پروژه/"
git push origin develop
```

سپس همه:

```bash
git fetch origin && git pull origin develop
ls   # باید اسناد و پروژه را ببینید
```

## بعد از بازآرایی

کار روزمره کد:

```bash
cd پروژه
```

مسیرهای داخل `AGENT_RULES` و INDEX را در صورت نیاز به `اسناد/` به‌روز کنید.
