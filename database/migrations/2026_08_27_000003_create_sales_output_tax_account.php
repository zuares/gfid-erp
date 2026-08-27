<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::table('accounts')->updateOrInsert(['code' => '2201'], [
            'code' => '2201',
            'name' => 'PPN Keluaran',
            'type' => 'liability',
            'is_cash' => 0,
            'is_active' => 1,
        ]);
    }

    public function down(): void
    {
        $accountId = DB::table('accounts')->where('code', '2201')->value('id');

        if ($accountId && ! DB::table('journal_lines')->where('account_id', $accountId)->exists()) {
            DB::table('accounts')->where('id', $accountId)->delete();
        }
    }
};
