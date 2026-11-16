<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::table('permissions')
            ->where('name', 'companies.manage')
            ->whereIn('group', ['سیستم', 'system'])
            ->update(['group' => 'شرکت']);
    }

    public function down(): void
    {
        DB::table('permissions')
            ->where('name', 'companies.manage')
            ->where('group', 'شرکت')
            ->update(['group' => 'سیستم']);
    }
};
