<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('packaging_model', function (Blueprint $table) {
            $table->id();
            $table->foreignId('packaging_type_id')->constrained()->cascadeOnDelete();
            $table->foreignId('product_model_id')->constrained()->cascadeOnDelete();
            $table->string('availability')->default('allowed');
            $table->boolean('is_preferred')->default(false);
            $table->timestamps();
            $table->unique(['packaging_type_id', 'product_model_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('packaging_model');
    }
};
