<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('product_models', function (Blueprint $table) {
            $table->foreignId('product_line_id')->nullable()->after('company_id')->constrained()->nullOnDelete();
            $table->json('attributes')->nullable()->after('description');
        });

        Schema::table('product_sizes', function (Blueprint $table) {
            $table->foreignId('product_line_id')->nullable()->after('company_id')->constrained()->nullOnDelete();
            $table->json('attributes')->nullable()->after('name');
        });

        Schema::table('cover_skus', function (Blueprint $table) {
            $table->foreignId('product_line_id')->nullable()->after('company_id')->constrained()->nullOnDelete();
            $table->foreignId('cover_type_id')->nullable()->after('product_line_id')->constrained()->nullOnDelete();
            $table->string('series_type')->default('custom')->after('sku');
            $table->json('attributes')->nullable()->after('structure');
        });

        Schema::table('product_options', function (Blueprint $table) {
            $table->foreignId('product_line_id')->nullable()->after('company_id')->constrained()->nullOnDelete();
            $table->json('attributes')->nullable()->after('type');
        });

        Schema::table('products', function (Blueprint $table) {
            $table->foreignId('product_line_id')->nullable()->after('company_id')->constrained()->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('product_models', function (Blueprint $table) {
            $table->dropConstrainedForeignId('product_line_id');
            $table->dropColumn('attributes');
        });
        Schema::table('product_sizes', function (Blueprint $table) {
            $table->dropConstrainedForeignId('product_line_id');
            $table->dropColumn('attributes');
        });
        Schema::table('cover_skus', function (Blueprint $table) {
            $table->dropConstrainedForeignId('product_line_id');
            $table->dropConstrainedForeignId('cover_type_id');
            $table->dropColumn(['series_type', 'attributes']);
        });
        Schema::table('product_options', function (Blueprint $table) {
            $table->dropConstrainedForeignId('product_line_id');
            $table->dropColumn('attributes');
        });
        Schema::table('products', function (Blueprint $table) {
            $table->dropConstrainedForeignId('product_line_id');
        });
    }
};
