<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('cover_sku_stocks', function (Blueprint $table) {
            $table->id();
            $table->foreignId('cover_sku_id')->constrained()->cascadeOnDelete();
            $table->foreignId('color_primary_id')->constrained('cover_colors')->cascadeOnDelete();
            $table->foreignId('color_secondary_id')->nullable()->constrained('cover_colors')->nullOnDelete();
            $table->unsignedInteger('stock_qty')->default(0);
            $table->string('note')->nullable();
            $table->timestamps();
            $table->unique(['cover_sku_id', 'color_primary_id', 'color_secondary_id'], 'cover_sku_color_unique');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('cover_sku_stocks');
    }
};
