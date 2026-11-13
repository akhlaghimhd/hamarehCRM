<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasColumn('opportunities', 'product_interest')) {
            return;
        }

        $rows = DB::table('opportunities')
            ->whereNotNull('product_interest')
            ->where('product_interest', '!=', '')
            ->get(['id', 'product_interest']);

        $map = [
            'notopia_yearbook' => 'notopia',
            'notopia_notebook' => 'notopia',
            'corporate_gift' => 'notopia',
            'other' => 'other',
        ];

        // موقتاً به text برای JSON امن
        DB::statement('ALTER TABLE opportunities MODIFY product_interest TEXT NULL');

        foreach ($rows as $row) {
            $raw = trim((string) $row->product_interest);
            if ($raw === '' || str_starts_with($raw, '[')) {
                continue;
            }
            $code = $map[$raw] ?? $raw;
            DB::table('opportunities')->where('id', $row->id)->update([
                'product_interest' => json_encode([$code], JSON_UNESCAPED_UNICODE),
            ]);
        }

        // MariaDB / MySQL JSON
        DB::statement('ALTER TABLE opportunities MODIFY product_interest JSON NULL');
    }

    public function down(): void
    {
        if (! Schema::hasColumn('opportunities', 'product_interest')) {
            return;
        }

        $rows = DB::table('opportunities')->whereNotNull('product_interest')->get(['id', 'product_interest']);

        DB::statement('ALTER TABLE opportunities MODIFY product_interest VARCHAR(255) NULL');

        foreach ($rows as $row) {
            $decoded = json_decode((string) $row->product_interest, true);
            $first = is_array($decoded) ? ($decoded[0] ?? null) : $row->product_interest;
            DB::table('opportunities')->where('id', $row->id)->update([
                'product_interest' => $first,
            ]);
        }
    }
};
