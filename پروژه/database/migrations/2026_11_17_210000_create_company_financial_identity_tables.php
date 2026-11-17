<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

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
            $table->foreignId('company_financial_identity_id')->constrained('company_financial_identities', 'id', 'bank_account_identity_fk')->restrictOnDelete();
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
            $table->index(['company_id', 'company_financial_identity_id'], 'bank_accounts_company_identity_idx');
            $table->index(['company_financial_identity_id', 'is_default', 'is_active'], 'bank_accounts_default_lookup');
        });

        Schema::create('company_financial_identity_versions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained()->cascadeOnDelete();
            $table->foreignId('company_financial_identity_id')->constrained('company_financial_identities', 'id', 'financial_identity_version_identity_fk')->restrictOnDelete();
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

        $now = now();
        foreach (DB::table('companies')->get() as $company) {
            foreach (['official', 'unofficial'] as $type) {
                $identityData = [
                    'company_id' => $company->id,
                    'type' => $type,
                    'legal_name' => $company->name,
                    'national_id' => $company->national_id,
                    'economic_code' => null,
                    'registration_number' => $company->registration_number,
                    'phone' => $company->phone,
                    'email' => $company->email,
                    'postal_code' => null,
                    'address' => $company->address,
                    'website' => $company->website ?? null,
                    'version' => 1,
                    'is_active' => true,
                    'created_at' => $now,
                    'updated_at' => $now,
                ];
                $identityId = DB::table('company_financial_identities')->insertGetId($identityData);
                $snapshot = $identityData;
                unset($snapshot['created_at'], $snapshot['updated_at']);

                DB::table('company_financial_identity_versions')->insert([
                    'company_id' => $company->id,
                    'company_financial_identity_id' => $identityId,
                    'version' => 1,
                    'snapshot' => json_encode($snapshot, JSON_THROW_ON_ERROR | JSON_UNESCAPED_UNICODE),
                    'created_by_user_id' => null,
                    'created_at' => $now,
                    'updated_at' => $now,
                ]);
            }
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('company_bank_account_versions');
        Schema::dropIfExists('company_financial_identity_versions');
        Schema::dropIfExists('company_bank_accounts');
        Schema::dropIfExists('company_financial_identities');
    }
};
