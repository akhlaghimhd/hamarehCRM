<?php

return [

    // فعال/غیرفعال بودن پکیج
    'enabled' => true,

    // زبان پیش‌فرض
    'locale' => 'fa',
    'fallback_locale' => 'en',

    // جهت رابط
    'direction' => 'rtl',

    // منطقه زمانی
    'timezone' => 'Asia/Tehran',

    // فونت
    'font' => [
        'family' => 'Vazirmatn',
        'url' => 'https://cdn.jsdelivr.net/gh/rastikerdar/vazirmatn@v33.003/Vazirmatn-font-face.css',
    ],

    // تقویم
    'calendar' => [
        'type' => 'jalali',
        'digits' => 'persian',

        'months' => [
            'فروردین', 'اردیبهشت', 'خرداد', 'تیر', 'مرداد', 'شهریور',
            'مهر', 'آبان', 'آذر', 'دی', 'بهمن', 'اسفند',
        ],

        'weekdays' => [
            'شنبه', 'یک‌شنبه', 'دوشنبه', 'سه‌شنبه', 'چهارشنبه', 'پنج‌شنبه', 'جمعه',
        ],

        'weekdays_short' => ['ش', 'ی', 'د', 'س', 'چ', 'پ', 'ج'],

        'format' => [
            'date'     => 'Y/m/d',
            'datetime' => 'Y/m/d H:i',
            'time'     => 'H:i',
        ],
    ],

    // اعداد
    'numbers' => [
        'convert_in_ui' => true,
        'thousand_separator' => '٬',
        'decimal_separator'  => '٫',

        'currency' => [
            'default' => 'IRT',
            'symbols' => [
                'IRT' => 'تومان',
                'IRR' => 'ریال',
            ],
        ],
    ],

    // تم
    'theme' => [
        'primary_color' => '#0ea5e9',
        'radius' => '0.75rem',
    ],

];