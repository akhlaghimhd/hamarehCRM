<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('product_models', function (Blueprint $table) {
            $table->foreignId('product_family_id')
                ->nullable()
                ->after('product_line_id')
                ->constrained('product_families')
                ->nullOnDelete();
            $table->foreignId('catalog_season_id')
                ->nullable()
                ->after('product_family_id')
                ->constrained('catalog_seasons')
                ->nullOnDelete();
            $table->string('status', 20)->default('draft')->after('catalog_season_id');
            $table->text('internal_note')->nullable()->after('description');
            $table->index(['company_id', 'status']);
            $table->index(['product_family_id', 'catalog_season_id']);
        });
    }

    public function down(): void
    {
        Schema::table('product_models', function (Blueprint $table) {
            $table->dropConstrainedForeignId('product_family_id');
            $table->dropConstrainedForeignId('catalog_season_id');
            $table->dropColumn(['status', 'internal_note']);
        });
    }
};
