<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // تبدیل مقدار تک‌رشته‌ای قبلی به JSON آرایه
        $rows = DB::table('opportunities')
            ->whereNotNull('product_interest')
            ->where('product_interest', '!=', '')
            ->get(['id', 'product_interest']);

        Schema::table('opportunities', function (Blueprint $table) {
            $table->json('product_interest_json')->nullable()->after('industry_other');
        });

        foreach ($rows as $row) {
            $raw = trim((string) $row->product_interest);
            if ($raw === '') {
                continue;
            }
            // اگر از قبل JSON بوده
            $decoded = json_decode($raw, true);
            $codes = is_array($decoded) ? array_values($decoded) : [$raw];

            // نگاشت کدهای قدیمی فرم به کد خط محصول
            $map = [
                'notopia_yearbook' => 'notopia',
                'notopia_notebook' => 'notopia',
                'corporate_gift' => 'notopia',
                'other' => 'other',
            ];
            $codes = array_values(array_unique(array_map(
                fn ($c) => $map[$c] ?? $c,
                $codes
            )));

            DB::table('opportunities')->where('id', $row->id)->update([
                'product_interest_json' => json_encode($codes, JSON_UNESCAPED_UNICODE),
            ]);
        }

        Schema::table('opportunities', function (Blueprint $table) {
            $table->dropColumn('product_interest');
        });

        Schema::table('opportunities', function (Blueprint $table) {
            $table->renameColumn('product_interest_json', 'product_interest');
        });
    }

    public function down(): void
    {
        Schema::table('opportunities', function (Blueprint $table) {
            $table->string('product_interest_str')->nullable()->after('industry_other');
        });

        $rows = DB::table('opportunities')->whereNotNull('product_interest')->get(['id', 'product_interest']);
        foreach ($rows as $row) {
            $decoded = json_decode((string) $row->product_interest, true);
            $first = is_array($decoded) ? ($decoded[0] ?? null) : $row->product_interest;
            DB::table('opportunities')->where('id', $row->id)->update([
                'product_interest_str' => $first,
            ]);
        }

        Schema::table('opportunities', function (Blueprint $table) {
            $table->dropColumn('product_interest');
        });

        Schema::table('opportunities', function (Blueprint $table) {
            $table->renameColumn('product_interest_str', 'product_interest');
        });
    }
};
