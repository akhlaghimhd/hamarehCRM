<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('addon_model_rules', function (Blueprint $table) {
            $table->id();
            $table->foreignId('product_option_id')->constrained()->cascadeOnDelete();
            $table->foreignId('product_model_id')->constrained()->cascadeOnDelete();
            $table->string('availability')->default('allowed');
            $table->boolean('is_preferred')->default(false);
            $table->string('note')->nullable();
            $table->timestamps();
            $table->unique(['product_option_id', 'product_model_id'], 'addon_model_unique');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('addon_model_rules');
    }
};
