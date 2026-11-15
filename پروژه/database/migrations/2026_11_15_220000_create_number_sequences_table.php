<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('number_sequences', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained()->cascadeOnDelete();
            $table->string('sequence_key', 100);
            $table->string('period', 20);
            $table->string('prefix', 40);
            $table->unsignedBigInteger('next_number')->default(1);
            $table->unsignedTinyInteger('padding')->default(4);
            $table->timestamps();

            $table->unique(
                ['company_id', 'sequence_key', 'period'],
                'number_sequences_company_key_period_unique',
            );
        });

        $this->backfillExistingNumbers('quotes', 'quote', 'Q-');
        $this->backfillExistingNumbers('orders', 'order', 'O-');
    }

    private function backfillExistingNumbers(string $table, string $key, string $prefixBase): void
    {
        if (! Schema::hasTable($table)) {
            return;
        }

        $groups = [];

        foreach (DB::table($table)->whereNotNull('number')->select('company_id', 'number')->orderBy('id')->get() as $record) {
            if (! preg_match('/^'.preg_quote($prefixBase, '/').'([0-9]{4})-([0-9]+)$/', (string) $record->number, $matches)) {
                continue;
            }

            $period = $matches[1];
            $groupKey = $record->company_id.'|'.$period;
            $groups[$groupKey] = [
                'company_id' => (int) $record->company_id,
                'period' => $period,
                'prefix' => $prefixBase.$period.'-',
                'maximum' => max($groups[$groupKey]['maximum'] ?? 0, (int) $matches[2]),
            ];
        }

        foreach ($groups as $group) {
            DB::table('number_sequences')->insertOrIgnore([
                'company_id' => $group['company_id'],
                'sequence_key' => $key,
                'period' => $group['period'],
                'prefix' => $group['prefix'],
                'next_number' => $group['maximum'] + 1,
                'padding' => 4,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('number_sequences');
    }
};
