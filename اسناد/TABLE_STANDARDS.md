# استاندارد جداول Filament (myoffice / Notopia)

آخرین به‌روزرسانی: **2026-11-09**

هدف: یکدست‌سازی همه ListRecords.

1. recordUrl(null) و در صورت نیاز recordAction(null)
2. ویرایش فقط از لینک نام و/یا اکشن ردیف
3. is_active با IconColumn + Confirm + یادآوری سفارش باز
4. نوع/محدوده مدل ستون جدا با badge
5. اعداد فقط App\Support\Fa
6. فیلتر پیش‌فرض Filament (نه AboveContent سنگین)
7. Soft delete: TrashedFilter + Restore + ForceDelete
8. emptyState با Create
9. paginated([10, 15, 25, 50, 100]) بدون کلید فارسی
10. defaultSort مشخص
11. Bulk حساس با Confirm
12. چگالی از CSS سراسری

## اعمال روی Resource جدید
قبل از merge موارد ۱–۱۱ را تیک بزن.
