<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('product_models', function (Blueprint $table) {
            if (! Schema::hasColumn('product_models', 'base_price')) {
                $table->decimal('base_price', 15, 0)->nullable()->after('description');
            }
            if (! Schema::hasColumn('product_models', 'cost_price')) {
                $table->decimal('cost_price', 15, 0)->nullable()->after('base_price');
            }
        });
    }

    public function down(): void
    {
        Schema::table('product_models', function (Blueprint $table) {
            $table->dropColumn(['base_price', 'cost_price']);
        });
    }
};
