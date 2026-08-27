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

        DB::table('accounts')->updateOrInsert(['code' => '1304'], [
            'code' => '1304',
            'name' => 'Saldo Iklan Marketplace / Prepaid',
            'type' => 'asset',
            'is_cash' => false,
            'is_active' => true,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    public function down(): void
    {
        if (! Schema::hasTable('accounts')) {
            return;
        }

        $accountId = DB::table('accounts')->where('code', '1304')->value('id');
        if ($accountId && ! DB::table('journal_lines')->where('account_id', $accountId)->exists()) {
            DB::table('accounts')->where('id', $accountId)->delete();
        }
    }
};
