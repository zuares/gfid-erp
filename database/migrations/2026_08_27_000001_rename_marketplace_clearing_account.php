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

        DB::table('accounts')
            ->where('code', '1302')
            ->update([
                'name' => 'Saldo Marketplace / Clearing',
                'updated_at' => now(),
            ]);
    }

    public function down(): void
    {
        if (! Schema::hasTable('accounts')) {
            return;
        }

        DB::table('accounts')
            ->where('code', '1302')
            ->where('name', 'Saldo Marketplace / Clearing')
            ->update([
                'name' => 'Piutang Marketplace',
                'updated_at' => now(),
            ]);
    }
};
