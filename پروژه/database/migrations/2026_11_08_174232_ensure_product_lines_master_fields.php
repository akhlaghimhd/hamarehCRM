<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('product_lines', function (Blueprint $table) {
            if (! Schema::hasColumn('product_lines', 'type')) {
                $table->string('type')->default('configurable')->after('name');
            }
            if (! Schema::hasColumn('product_lines', 'lifecycle_status')) {
                $table->string('lifecycle_status')->default('active')->after('type');
            }
            if (! Schema::hasColumn('product_lines', 'brand')) {
                $table->string('brand')->nullable()->after('lifecycle_status');
            }
            if (! Schema::hasColumn('product_lines', 'unit')) {
                $table->string('unit')->default('عدد')->after('brand');
            }
            if (! Schema::hasColumn('product_lines', 'short_description')) {
                $table->text('short_description')->nullable()->after('description');
            }
            if (! Schema::hasColumn('product_lines', 'attributes')) {
                $table->json('attributes')->nullable()->after('short_description');
            }
            // قیمت عمداً روی خانواده نگه داشته نمی‌شود؛ اگر از قبل هست استفاده نمی‌کنیم
        });
    }

    public function down(): void
    {
        //
    }
};
