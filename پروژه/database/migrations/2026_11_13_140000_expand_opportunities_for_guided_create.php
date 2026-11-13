<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('opportunities', function (Blueprint $table) {
            $table->string('organization_name')->nullable()->after('title');
            $table->string('contact_name')->nullable()->after('organization_name');
            $table->string('contact_role')->nullable()->after('contact_name');
            $table->string('mobile', 30)->nullable()->after('contact_role');
            $table->string('phone', 30)->nullable()->after('mobile');
            $table->string('email')->nullable()->after('phone');
            $table->string('address')->nullable()->after('email');
            $table->string('industry')->nullable()->after('address');
            $table->string('industry_other')->nullable()->after('industry');
            $table->string('product_interest')->nullable()->after('industry_other');
            $table->string('purchase_use')->nullable()->after('product_interest');
            $table->string('qty_approx')->nullable()->after('purchase_use');
            $table->string('budget_band')->nullable()->after('qty_approx');
            $table->string('buy_timing')->nullable()->after('budget_band');
            $table->string('value_focus')->nullable()->after('buy_timing');
            $table->string('value_other')->nullable()->after('value_focus');
            $table->text('main_objection')->nullable()->after('value_other');
            $table->string('status', 30)->default('active')->after('main_objection');
            $table->string('lost_reason_code')->nullable()->after('lost_reason');
            $table->text('final_result')->nullable()->after('lost_reason_code');
            $table->string('next_step')->nullable()->after('final_result');
            $table->date('next_step_due')->nullable()->after('next_step');
            $table->foreignId('followup_user_id')->nullable()->after('next_step_due')->constrained('users')->nullOnDelete();
        });

        Schema::table('opportunity_stages', function (Blueprint $table) {
            $table->string('description')->nullable()->after('name');
        });
    }

    public function down(): void
    {
        Schema::table('opportunities', function (Blueprint $table) {
            $table->dropConstrainedForeignId('followup_user_id');
            $table->dropColumn([
                'organization_name',
                'contact_name',
                'contact_role',
                'mobile',
                'phone',
                'email',
                'address',
                'industry',
                'industry_other',
                'product_interest',
                'purchase_use',
                'qty_approx',
                'budget_band',
                'buy_timing',
                'value_focus',
                'value_other',
                'main_objection',
                'status',
                'lost_reason_code',
                'final_result',
                'next_step',
                'next_step_due',
            ]);
        });

        Schema::table('opportunity_stages', function (Blueprint $table) {
            $table->dropColumn('description');
        });
    }
};
