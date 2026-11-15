<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('product_option_attributes', function (Blueprint $table) {
            if (! Schema::hasColumn('product_option_attributes', 'affects_inventory')) {
                $table->boolean('affects_inventory')->default(false)->after('is_required');
            }
        });
    }

    public function down(): void
    {
        Schema::table('product_option_attributes', function (Blueprint $table) {
            if (Schema::hasColumn('product_option_attributes', 'affects_inventory')) {
                $table->dropColumn('affects_inventory');
            }
        });
    }
};
