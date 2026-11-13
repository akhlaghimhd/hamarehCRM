<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('product_options', function (Blueprint $table) {
            if (! Schema::hasColumn('product_options', 'unit_price')) {
                $table->decimal('unit_price', 15, 0)->nullable()->after('type');
            }
            if (! Schema::hasColumn('product_options', 'stock_qty')) {
                $table->unsignedInteger('stock_qty')->default(0)->after('unit_price');
            }
            if (! Schema::hasColumn('product_options', 'model_scope')) {
                // all = همه مدل‌ها | selected = فقط مدل‌های انتخاب‌شده
                $table->string('model_scope')->default('all')->after('stock_qty');
            }
        });
    }

    public function down(): void
    {
        Schema::table('product_options', function (Blueprint $table) {
            foreach (['unit_price', 'stock_qty', 'model_scope'] as $col) {
                if (Schema::hasColumn('product_options', $col)) {
                    $table->dropColumn($col);
                }
            }
        });
    }
};
