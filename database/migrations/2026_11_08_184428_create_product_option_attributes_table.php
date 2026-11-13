<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('product_option_attributes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('product_option_id')->constrained()->cascadeOnDelete();
            $table->string('key');
            $table->string('label');
            $table->string('value_type')->default('text');
            // text | number | boolean | select | multiselect | color
            $table->json('options_json')->nullable(); // برای select/multiselect
            $table->text('value')->nullable();
            $table->boolean('is_required')->default(false);
            $table->unsignedInteger('sort')->default(0);
            $table->timestamps();
            $table->unique(['product_option_id', 'key']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('product_option_attributes');
    }
};
