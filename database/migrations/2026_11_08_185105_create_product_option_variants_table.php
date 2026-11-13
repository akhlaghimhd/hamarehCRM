<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('product_option_variants', function (Blueprint $table) {
            $table->id();
            $table->foreignId('product_option_id')->constrained()->cascadeOnDelete();
            // ترکیب مقادیر ویژگی‌هایی که affects_inventory=true دارند
            // مثال: {"color":"آبی","type":"پاک‌شو"}
            $table->json('combination');
            $table->string('label')->nullable(); // نمایش خوانا
            $table->decimal('unit_price', 15, 0)->nullable();
            $table->unsignedInteger('stock_qty')->default(0);
            $table->string('sku')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('product_option_variants');
    }
};
