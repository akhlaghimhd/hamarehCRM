# اسناد hamarehCRM

مستندات رسمی پروژه.

## شروع سریع

1. [00_AI_AND_HUMAN_INTRODUCTION.md](./00_AI_AND_HUMAN_INTRODUCTION.md)
2. [ONBOARDING_CHECKLIST.md](./ONBOARDING_CHECKLIST.md)

## معماری و قوانین سیستم (فعلاً مسیر موازی)

تا تکمیل انتقال کامل، بستهٔ اصلی اینجا هم هست:

- در مخزن فعلی: **`docs/system/`** (فعال و کامل روی `develop`)
- هدف نهایی: **`اسناد/system/`** + کد زیر **`پروژه/`**

فهرست:

| فایل | موضوع |
|------|--------|
| `docs/system/00_INDEX.md` | فهرست |
| `docs/system/01_ARCHITECTURE.md` | معماری |
| `docs/system/02_RULES_AND_GOVERNANCE.md` | باید/نباید |
| `docs/system/03_ROADMAP_AND_PRIORITIES.md` | نقشه راه ۱۴روزه |
| `docs/system/modules/M00`…`M11` | ریز کار هر ماژول |
| `docs/AGENT_RULES.md` | قوانین عامل AI |

## بازآرایی ریشه مخزن

هدف:

```
اسناد/   +   پروژه/
```

انتقال کامل درخت Laravel به `پروژه/` باید با `git mv` روی ماشینی که Push دارد انجام شود (API فایل‌به‌فایل برای کل اپ عملی نیست). اسکریپت پیشنهادی در ریشه مخزن: در صورت وجود `scripts/restructure-to-asnad-projeh.sh` یا دستورهای لید فنی.
