<?php

namespace App\Support;

use Illuminate\Support\Facades\DB;
use InvalidArgumentException;
use RuntimeException;

class NumberSequence
{
    public function next(
        int $companyId,
        string $sequenceKey,
        string $prefix,
        int $padding = 4,
        ?string $period = null,
    ): string {
        if ($companyId < 1 || trim($sequenceKey) === '' || strlen($sequenceKey) > 100) {
            throw new InvalidArgumentException('مشخصات شرکت یا کلید شماره‌گذاری معتبر نیست.');
        }

        if ($prefix === '' || strlen($prefix) > 40 || $padding < 1 || $padding > 12) {
            throw new InvalidArgumentException('پیشوند یا طول شماره معتبر نیست.');
        }

        $contextCompanyId = app(CompanyContext::class)->requireCompanyId();

        if ($contextCompanyId !== $companyId) {
            throw new RuntimeException('صدور شماره برای شرکتی غیر از شرکت جاری مجاز نیست.');
        }

        $period ??= now()->format('Y');

        if ($period === '' || strlen($period) > 20) {
            throw new InvalidArgumentException('دوره شماره‌گذاری معتبر نیست.');
        }

        return DB::transaction(function () use ($companyId, $sequenceKey, $prefix, $padding, $period): string {
            $now = now();

            DB::table('number_sequences')->insertOrIgnore([
                'company_id' => $companyId,
                'sequence_key' => $sequenceKey,
                'period' => $period,
                'prefix' => $prefix,
                'next_number' => 1,
                'padding' => $padding,
                'created_at' => $now,
                'updated_at' => $now,
            ]);

            $sequence = DB::table('number_sequences')
                ->where('company_id', $companyId)
                ->where('sequence_key', $sequenceKey)
                ->where('period', $period)
                ->lockForUpdate()
                ->first();

            if (! $sequence) {
                throw new RuntimeException('ردیف شماره‌گذاری قابل دریافت نیست.');
            }

            if ($sequence->prefix !== $prefix || (int) $sequence->padding !== $padding) {
                throw new RuntimeException('تعریف شماره‌گذاری موجود با تنظیمات درخواستی سازگار نیست.');
            }

            $number = $prefix.str_pad(
                (string) $sequence->next_number,
                $padding,
                '0',
                STR_PAD_LEFT,
            );

            DB::table('number_sequences')
                ->where('company_id', $companyId)
                ->where('sequence_key', $sequenceKey)
                ->where('period', $period)
                ->update([
                    'next_number' => ((int) $sequence->next_number) + 1,
                    'updated_at' => $now,
                ]);

            return $number;
        }, 5);
    }
}
