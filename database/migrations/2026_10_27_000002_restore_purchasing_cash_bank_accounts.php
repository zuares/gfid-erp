<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    private const ACCOUNTS = [
        ['code' => '1101', 'name' => 'Kas Tunai'],
        ['code' => '1111', 'name' => 'Bank Jago (Bisnis)'],
        ['code' => '1112', 'name' => 'Bank BCA (Operasional)'],
        ['code' => '1113', 'name' => 'Seabank'],
        ['code' => '1114', 'name' => 'Aladin Bank'],
    ];

    public function up(): void
    {
        $now = now();

        foreach (self::ACCOUNTS as $account) {
            DB::table('accounts')->updateOrInsert(
                ['code' => $account['code']],
                [
                    'name' => $account['name'],
                    'type' => 'asset',
                    'is_cash' => true,
                    'is_active' => true,
                    'updated_at' => $now,
                    'created_at' => $now,
                ],
            );
        }
    }

    public function down(): void
    {
        $codes = collect(self::ACCOUNTS)->pluck('code');
        $accountIds = DB::table('accounts')->whereIn('code', $codes)->pluck('id');

        if ($accountIds->isEmpty()) {
            return;
        }

        $hasJournalLines = DB::table('journal_lines')->whereIn('account_id', $accountIds)->exists();
        $hasPayments = DB::table('purchase_payments')->whereIn('cash_account_id', $accountIds)->exists();

        if ($hasJournalLines || $hasPayments) {
            throw new RuntimeException('Akun kas/bank purchasing sudah dipakai dan tidak boleh dihapus saat rollback.');
        }

        DB::table('accounts')->whereIn('id', $accountIds)->delete();
    }
};
