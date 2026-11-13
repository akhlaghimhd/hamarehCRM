<?php

namespace App\Support;

use Carbon\Carbon;

class Persian
{
    public static function digits(?string $value): string
    {
        if ($value === null || $value === '') {
            return '';
        }

        return strtr((string) $value, [
            '0' => '۰', '1' => '۱', '2' => '۲', '3' => '۳', '4' => '۴',
            '5' => '۵', '6' => '۶', '7' => '۷', '8' => '۸', '9' => '۹',
        ]);
    }

    public static function dateTime(mixed $value): string
    {
        if (blank($value)) {
            return '—';
        }

        try {
            $carbon = Carbon::parse($value);

            if (class_exists(\Morilog\Jalali\Jalalian::class)) {
                return self::digits(
                    \Morilog\Jalali\Jalalian::fromCarbon($carbon)->format('Y/m/d H:i')
                );
            }

            return self::digits($carbon->format('Y/m/d H:i'));
        } catch (\Throwable) {
            return self::digits((string) $value);
        }
    }
}
