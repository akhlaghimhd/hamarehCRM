<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('opportunity_steps', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained()->cascadeOnDelete();
            $table->foreignId('opportunity_id')->constrained()->cascadeOnDelete();
            $table->foreignId('assigned_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('title');
            $table->date('due_at')->nullable();
            $table->text('result')->nullable();
            $table->timestamp('closed_at')->nullable();
            $table->boolean('is_open')->default(true);
            $table->unsignedInteger('sort_order')->default(0);
            $table->timestamps();
            $table->softDeletes();

            $table->index(['opportunity_id', 'is_open']);
            $table->index(['company_id', 'opportunity_id']);
        });

        Schema::table('activities', function (Blueprint $table) {
            $table->foreignId('opportunity_step_id')
                ->nullable()
                ->after('opportunity_id')
                ->constrained('opportunity_steps')
                ->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('activities', function (Blueprint $table) {
            $table->dropConstrainedForeignId('opportunity_step_id');
        });

        Schema::dropIfExists('opportunity_steps');
    }
};
