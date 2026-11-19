<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class NormalizePersianDigits
{
    private const DIGITS = [
        '۰' => '0', '۱' => '1', '۲' => '2', '۳' => '3', '۴' => '4',
        '۵' => '5', '۶' => '6', '۷' => '7', '۸' => '8', '۹' => '9',
        '٠' => '0', '١' => '1', '٢' => '2', '٣' => '3', '٤' => '4',
        '٥' => '5', '٦' => '6', '٧' => '7', '٨' => '8', '٩' => '9',
    ];

    public function handle(Request $request, Closure $next): Response
    {
        $request->replace($this->normalizeArray($request->all()));

        return $next($request);
    }

    private function normalizeArray(array $values): array
    {
        foreach ($values as $key => $value) {
            if (in_array((string) $key, ['snapshot', 'checksum'], true)) {
                continue;
            }

            if (is_array($value)) {
                $values[$key] = $this->normalizeArray($value);
            } elseif (is_string($value)) {
                $values[$key] = strtr($value, self::DIGITS);
            }
        }

        return $values;
    }
}
