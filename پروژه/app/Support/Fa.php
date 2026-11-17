<?php

namespace App\Support;

use Illuminate\Support\Carbon;

class Fa
{
    public static function toEnglishDigits(null|int|float|string $value): string
    {
        if ($value === null || $value === '') {
            return '';
        }

        return strtr((string) $value, [
            '۰' => '0', '۱' => '1', '۲' => '2', '۳' => '3', '۴' => '4',
            '۵' => '5', '۶' => '6', '۷' => '7', '۸' => '8', '۹' => '9',
            '٠' => '0', '١' => '1', '٢' => '2', '٣' => '3', '٤' => '4',
            '٥' => '5', '٦' => '6', '٧' => '7', '٨' => '8', '٩' => '9',
        ]);
    }

    public static function digits(null|int|float|string $value): string
    {
        if ($value === null || $value === '') {
            return '—';
        }

        return strtr((string) $value, [
            '0' => '۰', '1' => '۱', '2' => '۲', '3' => '۳', '4' => '۴',
            '5' => '۵', '6' => '۶', '7' => '۷', '8' => '۸', '9' => '۹',
        ]);
    }

    public static function number(null|int|float|string $value, int $decimals = 0): string
    {
        if ($value === null || $value === '') {
            return '—';
        }

        return self::digits(number_format((float) self::toEnglishDigits($value), $decimals, '.', ','));
    }

    public static function dateTime(mixed $value, string $format = 'Y/m/d H:i'): string
    {
        if ($value === null || $value === '') {
            return '—';
        }

        try {
            $dt = $value instanceof \DateTimeInterface
                ? Carbon::instance(\Carbon\Carbon::parse($value))
                : Carbon::parse($value);

            if (class_exists(\Morilog\Jalali\Jalalian::class)) {
                return self::digits(\Morilog\Jalali\Jalalian::fromCarbon($dt)->format($format));
            }

            return self::digits($dt->format($format));
        } catch (\Throwable $e) {
            return '—';
        }
    }

    /**
     * تبدیل رشته تاریخ شمسی Y/m/d به Carbon میلادی.
     */
    public static function parseJalaliDate(?string $value): ?Carbon
    {
        if ($value === null || trim($value) === '') {
            return null;
        }

        $en = self::toEnglishDigits(trim($value));
        $en = str_replace(['-', '.'], '/', $en);

        if (! preg_match('/^(\d{4})\/(\d{1,2})\/(\d{1,2})$/', $en, $m)) {
            return null;
        }

        try {
            if (class_exists(\Morilog\Jalali\Jalalian::class)) {
                return \Morilog\Jalali\Jalalian::fromFormat('Y/n/j', sprintf('%d/%d/%d', (int) $m[1], (int) $m[2], (int) $m[3]))
                    ->toCarbon()
                    ->startOfDay();
            }
        } catch (\Throwable $e) {
            return null;
        }

        return null;
    }
}
