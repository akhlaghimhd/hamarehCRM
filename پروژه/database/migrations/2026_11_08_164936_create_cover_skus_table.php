<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('cover_skus', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained()->cascadeOnDelete();
            $table->string('sku')->comment('مثل P-200');
            $table->string('name');
            $table->string('material')->nullable()->comment('cardboard|leather_soft|leather_hard');
            $table->string('color')->nullable();
            $table->string('structure')->nullable()->comment('one_piece|two_piece');
            $table->unsignedInteger('stock_qty')->default(0);
            $table->boolean('is_active')->default(true);
            $table->timestamps();
            $table->softDeletes();

            $table->unique(['company_id', 'sku']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('cover_skus');
    }
};
