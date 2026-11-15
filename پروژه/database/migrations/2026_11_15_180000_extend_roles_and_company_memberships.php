<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('roles', function (Blueprint $table) {
            $table->foreignId('parent_role_id')->nullable()->after('company_id')->constrained('roles')->nullOnDelete();
            $table->boolean('inherit_permissions')->default(true)->after('is_system');
        });

        Schema::table('company_user', function (Blueprint $table) {
            $table->foreignId('manager_user_id')->nullable()->after('role_id')->constrained('users')->nullOnDelete();
        });

        Schema::create('company_user_role', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_user_id')->constrained('company_user')->cascadeOnDelete();
            $table->foreignId('role_id')->constrained()->cascadeOnDelete();
            $table->timestamps();
            $table->unique(['company_user_id', 'role_id']);
        });

        DB::table('company_user')
            ->whereNotNull('role_id')
            ->orderBy('id')
            ->get(['id', 'role_id'])
            ->each(function (object $membership): void {
                DB::table('company_user_role')->insertOrIgnore([
                    'company_user_id' => $membership->id,
                    'role_id' => $membership->role_id,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            });

        Schema::create('manager_membership_histories', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('company_id');
            $table->unsignedBigInteger('user_id');
            $table->unsignedBigInteger('old_manager_user_id')->nullable();
            $table->unsignedBigInteger('new_manager_user_id')->nullable();
            $table->unsignedBigInteger('changed_by_user_id')->nullable();
            $table->timestamp('changed_at');
            $table->timestamps();

            $table->index(['company_id', 'user_id', 'changed_at'], 'manager_history_lookup');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('manager_membership_histories');
        Schema::dropIfExists('company_user_role');

        Schema::table('company_user', function (Blueprint $table) {
            $table->dropConstrainedForeignId('manager_user_id');
        });

        Schema::table('roles', function (Blueprint $table) {
            $table->dropConstrainedForeignId('parent_role_id');
            $table->dropColumn('inherit_permissions');
        });
    }
};
