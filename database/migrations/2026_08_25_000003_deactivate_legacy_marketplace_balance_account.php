<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('accounts')) {
            return;
        }

        // 1302 is the canonical marketplace receivable account. Keep 1303
        // for historical journal readability, but prevent new postings from
        // selecting it as an active account.
        DB::table('accounts')
            ->where('code', '1303')
            ->update([
                'is_active' => false,
                'updated_at' => now(),
            ]);
    }

    public function down(): void
    {
        if (! Schema::hasTable('accounts')) {
            return;
        }

        DB::table('accounts')
            ->where('code', '1303')
            ->update([
                'is_active' => true,
                'updated_at' => now(),
            ]);
    }
};
