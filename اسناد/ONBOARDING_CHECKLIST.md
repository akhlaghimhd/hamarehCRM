# چک‌لیست آنبوردینگ — انسان و عامل AI

**مخزن:** `akhlaghimhd/hamarehCRM` · **برنچ:** `develop`

علامت بزنید؛ بدون اتمام بخش دسترسی، کدنویسی شروع نشود.

---

## الف) دسترسی

- [ ] دعوت Collaborator روی مخزن Accept شده (Write)
- [ ] SSH key یا HTTPS Token روی **اکانت گیت‌هاب خودم** ثبت شده
- [ ] `ssh -T git@github.com` پیام Hi با یوزرنیم خودم را می‌دهد
- [ ] (برای AI) کانکتور GitHub با scope نوشتن (`repo` / Contents write) وصل است

---

## ب) کلون و محیط

```bash
git clone git@github.com:akhlaghimhd/hamarehCRM.git
cd hamarehCRM
git checkout develop
git pull origin develop
```

- [ ] کلون موفق
- [ ] روی `develop` هستم
- [ ] PHP / Composer / Node مطابق پروژه (یا Docker طبق `compose.yaml`)
- [ ] `.env` از `.env.example` ساخته شده — **بدون commit**
- [ ] `composer install` و در صورت نیاز `npm install`
- [ ] migrate / seed تستی در محیط محلی
- [ ] پنل ادمین باز می‌شود (مثلاً `http://localhost:8088/admin`)

اگر کد هنوز در ریشه است (قبل از بازآرایی کامل):

```bash
# کار روزمره از ریشه مخزن
```

اگر پوشه `پروژه/` فعال شد:

```bash
cd پروژه
```

---

## ج) دانش (خواندن)

- [ ] `اسناد/00_AI_AND_HUMAN_INTRODUCTION.md`
- [ ] `docs/system/03_ROADMAP_AND_PRIORITIES.md`
- [ ] `docs/system/01_ARCHITECTURE.md`
- [ ] `docs/system/02_RULES_AND_GOVERNANCE.md`
- [ ] `docs/AGENT_RULES.md`
- [ ] سند ماژول اسکواد: `docs/system/modules/Mxx_*.md`

---

## د) نقش و اسکواد

- [ ] اسکواد من مشخص است (S0…S6 یا SQ)
- [ ] مالک تسک / لید فنی را می‌شناسم
- [ ] می‌دانم روی کدام ماژول (Mxx) کار می‌کنم

---

## هـ) گردش کار روزانه

```bash
git fetch origin
git pull origin develop
# … کار …
git add -A
git status
git commit -m "feat: short english message"
git push origin develop   # یا برنچ feature + PR
```

- [ ] Commit انگلیسی و معنادار
- [ ] بعد از pull دیگران: log و diff را می‌بینم
- [ ] Secret در کد یا چت نمی‌گذارم
- [ ] بدون `company_id` مدل/جدول عملیاتی جدید نمی‌سازم

---

## و) Definition of Done شخصی

- [ ] تغییر با قوانین `02_RULES` و `AGENT_RULES` سازگار است
- [ ] UI فارسی و بدون متن انگلیسی خام برای کاربر نهایی
- [ ] در صورت منطق کسب‌وکار: تست یا سناریوی دستی نوشته شده
- [ ] به لید گزارش فایل‌های تغییر کرده داده شده

---

## ز) مشکل رایج

| مشکل | کار |
|------|-----|
| Push رد می‌شود | SSH/Token و Collaborator را چک کن |
| AI فقط می‌خواند | Revoke و دوباره Connect با scope `repo` |
| ساختار لوکال با remote فرق دارد | `git fetch` + `git reset --hard origin/develop` فقط با تأیید لید |
| نمی‌دانم از کجا شروع کنم | Roadmap روز جاری + سند Mxx اسکواد |
