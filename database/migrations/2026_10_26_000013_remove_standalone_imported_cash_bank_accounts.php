<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
return new class extends Migration
{
    private const CODES = ['1101', '1111', '1112', '1113', '1114'];

    public function up(): void
    {
        $accountIds = DB::table('standalone_accounting_accounts')
            ->whereIn('code', self::CODES)
            ->pluck('id');

        if ($accountIds->isEmpty()) {
            return;
        }

        if (DB::table('standalone_accounting_journal_lines')->whereIn('account_id', $accountIds)->exists()) {
            throw new RuntimeException('Akun Kas/Bank detail tidak dapat dihapus karena sudah memiliki jurnal.');
        }

        DB::table('standalone_accounting_accounts')->whereIn('id', $accountIds)->delete();
    }

    public function down(): void
    {
        $now = now();

        foreach ([
            ['code' => '1101', 'name' => 'Kas Tunai', 'cash_category' => 'cash'],
            ['code' => '1111', 'name' => 'Bank Jago (Bisnis)', 'cash_category' => 'bank'],
            ['code' => '1112', 'name' => 'Bank BCA (Operasional)', 'cash_category' => 'bank'],
            ['code' => '1113', 'name' => 'Seabank', 'cash_category' => 'bank'],
            ['code' => '1114', 'name' => 'Aladin Bank', 'cash_category' => 'bank'],
        ] as $account) {
            DB::table('standalone_accounting_accounts')->updateOrInsert(
                ['code' => $account['code']],
                [
                    'name' => $account['name'],
                    'type' => 'asset',
                    'cash_category' => $account['cash_category'],
                    'source_code' => $account['code'],
                    'is_active' => true,
                    'created_at' => $now,
                    'updated_at' => $now,
                ],
            );
        }
    }
};
