<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('accounts') || DB::table('accounts')->where('code', '1303')->exists()) {
            return;
        }

        DB::table('accounts')->insert([
            'code'       => '1303',
            'name'       => 'Saldo Marketplace',
            'type'       => 'asset',
            'is_cash'    => false,
            'is_active'  => true,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    public function down(): void
    {
        if (! Schema::hasTable('accounts')) {
            return;
        }

        $accountId = DB::table('accounts')->where('code', '1303')->value('id');
        if (! $accountId) {
            return;
        }

        $hasJournalLines = DB::table('journal_lines')->where('account_id', $accountId)->exists();
        if (! $hasJournalLines) {
            DB::table('accounts')->where('id', $accountId)->delete();
        }
    }
};
