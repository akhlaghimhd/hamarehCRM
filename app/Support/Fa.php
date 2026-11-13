<?php

namespace App\Support;

use Illuminate\Support\Carbon;

class Fa
{
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

        return self::digits(number_format((float) $value, $decimals, '.', ','));
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
}
