<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('company_settings', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained()->cascadeOnDelete();
            $table->string('key', 100);
            $table->string('type', 20);
            $table->longText('value')->nullable();
            $table->json('validation_rules')->nullable();
            $table->boolean('is_sensitive')->default(false);
            $table->timestamps();

            $table->unique(['company_id', 'key']);
            $table->index(['company_id', 'type']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('company_settings');
    }
};
