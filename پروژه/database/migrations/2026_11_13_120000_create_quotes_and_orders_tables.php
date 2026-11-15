<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('quotes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained()->cascadeOnDelete();
            $table->foreignId('customer_id')->constrained()->cascadeOnDelete();
            $table->foreignId('opportunity_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('owner_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('number')->nullable();
            $table->string('title');
            $table->string('status', 32)->default('draft');
            $table->date('valid_until')->nullable();
            $table->decimal('subtotal', 18, 0)->default(0);
            $table->decimal('discount_amount', 18, 0)->default(0);
            $table->decimal('tax_amount', 18, 0)->default(0);
            $table->decimal('total', 18, 0)->default(0);
            $table->string('currency', 8)->default('IRR');
            $table->text('notes')->nullable();
            $table->timestamp('accepted_at')->nullable();
            $table->timestamp('converted_at')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->index(['company_id', 'status']);
            $table->unique(['company_id', 'number']);
        });

        Schema::create('quote_lines', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained()->cascadeOnDelete();
            $table->foreignId('quote_id')->constrained()->cascadeOnDelete();
            $table->unsignedInteger('sort_order')->default(10);
            $table->string('product_type', 64)->default('notopia');
            $table->unsignedBigInteger('product_model_id')->nullable();
            $table->string('title');
            $table->text('description')->nullable();
            $table->json('config_snapshot')->nullable();
            $table->decimal('qty', 12, 2)->default(1);
            $table->decimal('unit_price', 18, 0)->default(0);
            $table->decimal('line_total', 18, 0)->default(0);
            $table->timestamps();

            $table->index(['quote_id', 'sort_order']);
        });

        Schema::create('orders', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained()->cascadeOnDelete();
            $table->foreignId('customer_id')->constrained()->cascadeOnDelete();
            $table->foreignId('quote_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('opportunity_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('owner_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('number')->nullable();
            $table->string('title');
            $table->string('status', 32)->default('draft');
            $table->decimal('subtotal', 18, 0)->default(0);
            $table->decimal('discount_amount', 18, 0)->default(0);
            $table->decimal('tax_amount', 18, 0)->default(0);
            $table->decimal('total', 18, 0)->default(0);
            $table->string('currency', 8)->default('IRR');
            $table->text('notes')->nullable();
            $table->timestamp('confirmed_at')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->index(['company_id', 'status']);
            $table->unique(['company_id', 'number']);
        });

        Schema::create('order_lines', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained()->cascadeOnDelete();
            $table->foreignId('order_id')->constrained()->cascadeOnDelete();
            $table->unsignedInteger('sort_order')->default(10);
            $table->string('product_type', 64)->default('notopia');
            $table->unsignedBigInteger('product_model_id')->nullable();
            $table->string('title');
            $table->text('description')->nullable();
            $table->json('config_snapshot')->nullable();
            $table->decimal('qty', 12, 2)->default(1);
            $table->decimal('unit_price', 18, 0)->default(0);
            $table->decimal('line_total', 18, 0)->default(0);
            $table->timestamps();

            $table->index(['order_id', 'sort_order']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('order_lines');
        Schema::dropIfExists('orders');
        Schema::dropIfExists('quote_lines');
        Schema::dropIfExists('quotes');
    }
};
