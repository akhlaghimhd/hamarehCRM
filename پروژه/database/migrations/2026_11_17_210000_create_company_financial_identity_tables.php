<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('company_financial_identities', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained()->cascadeOnDelete();
            $table->string('type', 20);
            $table->string('legal_name')->nullable();
            $table->string('national_id', 32)->nullable();
            $table->string('economic_code', 64)->nullable();
            $table->string('registration_number', 64)->nullable();
            $table->string('phone', 32)->nullable();
            $table->string('email')->nullable();
            $table->string('postal_code', 20)->nullable();
            $table->text('address')->nullable();
            $table->string('website')->nullable();
            $table->unsignedInteger('version')->default(1);
            $table->boolean('is_active')->default(true);
            $table->timestamps();
            $table->unique(['company_id', 'type']);
            $table->index(['company_id', 'is_active']);
        });

        Schema::create('company_bank_accounts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained()->cascadeOnDelete();
            $table->foreignId('company_financial_identity_id')->constrained('company_financial_identities')->restrictOnDelete();
            $table->string('label')->nullable();
            $table->string('account_holder');
            $table->string('bank_name');
            $table->string('account_number', 64)->nullable();
            $table->string('iban', 64)->nullable();
            $table->string('card_number', 32)->nullable();
            $table->string('branch')->nullable();
            $table->unsignedInteger('version')->default(1);
            $table->boolean('is_default')->default(false);
            $table->boolean('is_active')->default(true);
            $table->timestamps();
            $table->index(['company_id', 'company_financial_identity_id']);
            $table->index(['company_financial_identity_id', 'is_default', 'is_active'], 'bank_accounts_default_lookup');
        });

        Schema::create('company_financial_identity_versions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained()->cascadeOnDelete();
            $table->foreignId('company_financial_identity_id')->constrained('company_financial_identities')->restrictOnDelete();
            $table->unsignedInteger('version');
            $table->json('snapshot');
            $table->foreignId('created_by_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->unique(['company_financial_identity_id', 'version'], 'financial_identity_version_unique');
        });

        Schema::create('company_bank_account_versions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained()->cascadeOnDelete();
            $table->foreignId('company_bank_account_id')->constrained('company_bank_accounts')->restrictOnDelete();
            $table->unsignedInteger('version');
            $table->json('snapshot');
            $table->foreignId('created_by_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->unique(['company_bank_account_id', 'version'], 'bank_account_version_unique');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('company_bank_account_versions');
        Schema::dropIfExists('company_financial_identity_versions');
        Schema::dropIfExists('company_bank_accounts');
        Schema::dropIfExists('company_financial_identities');
    }
};
