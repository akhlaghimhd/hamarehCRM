<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('invoices', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained()->cascadeOnDelete();
            $table->foreignId('order_id')->constrained()->restrictOnDelete();
            $table->foreignId('customer_id')->constrained()->restrictOnDelete();
            $table->foreignId('owner_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('number');
            $table->string('title');
            $table->string('status', 32)->default('issued');
            $table->string('identity_type', 20);
            $table->foreignId('company_financial_identity_id')->nullable()->constrained('company_financial_identities')->nullOnDelete();
            $table->foreignId('company_bank_account_id')->nullable()->constrained('company_bank_accounts')->nullOnDelete();
            $table->json('issuer_snapshot')->nullable();
            $table->json('bank_account_snapshot')->nullable();
            $table->decimal('subtotal', 18, 0)->default(0);
            $table->decimal('discount_amount', 18, 0)->default(0);
            $table->decimal('tax_amount', 18, 0)->default(0);
            $table->decimal('total', 18, 0)->default(0);
            $table->decimal('paid_amount', 18, 0)->default(0);
            $table->string('currency', 8)->default('IRR');
            $table->date('issued_on')->nullable();
            $table->date('due_on')->nullable();
            $table->text('notes')->nullable();
            $table->timestamp('cancelled_at')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->unique(['company_id', 'number']);
            $table->index(['company_id', 'status']);
            $table->index(['company_id', 'customer_id']);
            $table->index(['company_id', 'order_id']);
        });

        Schema::create('payments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained()->cascadeOnDelete();
            $table->foreignId('invoice_id')->constrained()->restrictOnDelete();
            $table->foreignId('customer_id')->constrained()->restrictOnDelete();
            $table->foreignId('recorded_by_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('number')->nullable();
            $table->decimal('amount', 18, 0);
            $table->string('currency', 8)->default('IRR');
            $table->string('method', 64)->nullable();
            $table->string('reference', 128)->nullable();
            $table->date('paid_on');
            $table->text('notes')->nullable();
            $table->timestamp('voided_at')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->index(['company_id', 'invoice_id']);
            $table->index(['company_id', 'paid_on']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('payments');
        Schema::dropIfExists('invoices');
    }
};
