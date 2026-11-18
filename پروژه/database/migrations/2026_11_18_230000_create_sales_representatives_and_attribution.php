<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('sales_representatives', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained()->cascadeOnDelete();
            $table->string('type', 20);
            $table->string('code', 50)->nullable();
            $table->string('name');
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('customer_id')->nullable()->constrained('customers')->nullOnDelete();
            $table->foreignId('parent_id')->nullable()->constrained('sales_representatives')->nullOnDelete();
            $table->string('status', 20)->default('active');
            $table->string('mobile')->nullable();
            $table->string('email')->nullable();
            $table->date('started_on')->nullable();
            $table->date('ended_on')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->unique(['company_id', 'code']);
            $table->index(['company_id', 'type', 'status']);
        });

        Schema::table('opportunities', function (Blueprint $table) {
            if (! Schema::hasColumn('opportunities', 'sales_representative_id')) {
                $table->foreignId('sales_representative_id')
                    ->nullable()
                    ->after('owner_user_id')
                    ->constrained('sales_representatives')
                    ->nullOnDelete();
            }
        });

        Schema::table('orders', function (Blueprint $table) {
            if (! Schema::hasColumn('orders', 'sales_representative_id')) {
                $table->foreignId('sales_representative_id')
                    ->nullable()
                    ->after('owner_user_id')
                    ->constrained('sales_representatives')
                    ->nullOnDelete();
            }
            if (! Schema::hasColumn('orders', 'sales_attribution_snapshot')) {
                $table->json('sales_attribution_snapshot')->nullable()->after('sales_representative_id');
            }
        });

        Schema::table('quotes', function (Blueprint $table) {
            if (! Schema::hasColumn('quotes', 'sales_representative_id')) {
                $table->foreignId('sales_representative_id')
                    ->nullable()
                    ->after('owner_user_id')
                    ->constrained('sales_representatives')
                    ->nullOnDelete();
            }
        });
    }

    public function down(): void
    {
        Schema::table('quotes', function (Blueprint $table) {
            if (Schema::hasColumn('quotes', 'sales_representative_id')) {
                $table->dropConstrainedForeignId('sales_representative_id');
            }
        });
        Schema::table('orders', function (Blueprint $table) {
            if (Schema::hasColumn('orders', 'sales_attribution_snapshot')) {
                $table->dropColumn('sales_attribution_snapshot');
            }
            if (Schema::hasColumn('orders', 'sales_representative_id')) {
                $table->dropConstrainedForeignId('sales_representative_id');
            }
        });
        Schema::table('opportunities', function (Blueprint $table) {
            if (Schema::hasColumn('opportunities', 'sales_representative_id')) {
                $table->dropConstrainedForeignId('sales_representative_id');
            }
        });
        Schema::dropIfExists('sales_representatives');
    }
};
