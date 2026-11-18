<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('stock_levels', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained()->cascadeOnDelete();
            $table->string('item_type');
            $table->unsignedBigInteger('item_id');
            $table->string('item_label')->nullable();
            $table->unsignedInteger('qty_on_hand')->default(0);
            $table->unsignedInteger('qty_reserved')->default(0);
            $table->unsignedInteger('qty_in_production')->default(0);
            $table->unsignedInteger('qty_ready')->default(0);
            $table->timestamps();

            $table->unique(['company_id', 'item_type', 'item_id'], 'stock_levels_company_item_unique');
            $table->index(['company_id', 'item_label']);
        });

        Schema::create('stock_movements', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained()->cascadeOnDelete();
            $table->foreignId('stock_level_id')->constrained('stock_levels')->cascadeOnDelete();
            $table->string('type', 40);
            $table->integer('qty');
            $table->unsignedInteger('qty_on_hand_after')->default(0);
            $table->unsignedInteger('qty_reserved_after')->default(0);
            $table->unsignedInteger('qty_in_production_after')->default(0);
            $table->unsignedInteger('qty_ready_after')->default(0);
            $table->nullableMorphs('reference');
            $table->string('reason')->nullable();
            $table->foreignId('actor_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('occurred_at');
            $table->timestamps();

            $table->index(['company_id', 'type', 'occurred_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('stock_movements');
        Schema::dropIfExists('stock_levels');
    }
};
