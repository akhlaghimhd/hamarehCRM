<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('product_lines', function (Blueprint $table) {
            $table->string('type')->default('configurable')->after('name');
            // configurable | simple | service | software
            $table->string('lifecycle_status')->default('active')->after('type');
            // draft | active | discontinued
            $table->string('brand')->nullable()->after('lifecycle_status');
            $table->string('unit')->default('عدد')->after('brand');
            $table->text('short_description')->nullable()->after('description');
            $table->decimal('base_price', 15, 0)->nullable()->after('short_description');
            $table->decimal('cost_price', 15, 0)->nullable()->after('base_price');
            $table->json('attributes')->nullable()->after('cost_price');
        });
    }

    public function down(): void
    {
        Schema::table('product_lines', function (Blueprint $table) {
            $table->dropColumn([
                'type', 'lifecycle_status', 'brand', 'unit',
                'short_description', 'base_price', 'cost_price', 'attributes',
            ]);
        });
    }
};
