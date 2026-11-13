<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('product_model_size', function (Blueprint $table) {
            $table->id();
            $table->foreignId('product_model_id')->constrained('product_models')->cascadeOnDelete();
            $table->foreignId('product_size_id')->constrained('product_sizes')->cascadeOnDelete();
            $table->unique(['product_model_id', 'product_size_id']);
        });

        Schema::create('product_model_cover_sku', function (Blueprint $table) {
            $table->id();
            $table->foreignId('product_model_id')->constrained('product_models')->cascadeOnDelete();
            $table->foreignId('cover_sku_id')->constrained('cover_skus')->cascadeOnDelete();
            $table->unique(['product_model_id', 'cover_sku_id']);
        });

        Schema::create('product_model_option', function (Blueprint $table) {
            $table->id();
            $table->foreignId('product_model_id')->constrained('product_models')->cascadeOnDelete();
            $table->foreignId('product_option_id')->constrained('product_options')->cascadeOnDelete();
            $table->boolean('is_recommended')->default(false);
            $table->unsignedInteger('min_qty')->nullable();
            $table->unsignedInteger('max_qty')->nullable();
            $table->unique(['product_model_id', 'product_option_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('product_model_option');
        Schema::dropIfExists('product_model_cover_sku');
        Schema::dropIfExists('product_model_size');
    }
};
