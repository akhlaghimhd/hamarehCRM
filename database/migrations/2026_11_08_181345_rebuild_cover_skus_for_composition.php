<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('cover_skus', function (Blueprint $table) {
            if (! Schema::hasColumn('cover_skus', 'hardness')) {
                $table->string('hardness')->nullable()->after('series_type'); // soft|hard
            }
            if (! Schema::hasColumn('cover_skus', 'cover_material_id')) {
                $table->foreignId('cover_material_id')->nullable()->after('hardness')->constrained()->nullOnDelete();
            }
            if (! Schema::hasColumn('cover_skus', 'cover_structure_id')) {
                $table->foreignId('cover_structure_id')->nullable()->after('cover_material_id')->constrained()->nullOnDelete();
            }
        });
    }

    public function down(): void
    {
        Schema::table('cover_skus', function (Blueprint $table) {
            if (Schema::hasColumn('cover_skus', 'cover_structure_id')) {
                $table->dropConstrainedForeignId('cover_structure_id');
            }
            if (Schema::hasColumn('cover_skus', 'cover_material_id')) {
                $table->dropConstrainedForeignId('cover_material_id');
            }
            if (Schema::hasColumn('cover_skus', 'hardness')) {
                $table->dropColumn('hardness');
            }
        });
    }
};
