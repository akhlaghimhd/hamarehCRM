<?php

namespace App\Http\Middleware;

use App\Support\Fa;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class NormalizePersianDigits
{
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
                $values[$key] = Fa::toEnglishDigits($value);
            }
        }

        return $values;
    }
}
