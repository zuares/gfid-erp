<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::table('standalone_accounting_account_mappings')->updateOrInsert(
            ['legacy_account_code' => '3101'],
            [
                'standalone_account_code' => '3-110',
                'purpose' => 'Modal pemilik sebagai akun lawan saldo awal inventory',
                'is_active' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ],
        );
    }

    public function down(): void
    {
        $accountId = DB::table('standalone_accounting_accounts')
            ->where('code', '3-110')
            ->value('id');

        if ($accountId && DB::table('standalone_accounting_journal_lines')->where('account_id', $accountId)->exists()) {
            throw new RuntimeException('Mapping 3101 sudah dipakai jurnal dan tidak boleh dihapus saat rollback.');
        }

        DB::table('standalone_accounting_account_mappings')
            ->where('legacy_account_code', '3101')
            ->delete();
    }
};
