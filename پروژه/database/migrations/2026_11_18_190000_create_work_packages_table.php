<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('work_packages', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained()->cascadeOnDelete();
            $table->foreignId('order_id')->constrained()->cascadeOnDelete();
            $table->foreignId('order_line_id')->nullable()->constrained('order_lines')->nullOnDelete();
            $table->string('number');
            $table->string('title');
            $table->text('description')->nullable();
            $table->string('execution_mode', 20)->default('internal');
            $table->string('status', 30)->default('draft');
            $table->string('contractor_name')->nullable();
            $table->foreignId('assignee_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('due_at')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->unique(['company_id', 'number']);
            $table->index(['company_id', 'order_id', 'status']);
            $table->index(['company_id', 'status', 'due_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('work_packages');
    }
};
