<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    private const DEFAULT_CODES = ['1000', '1100', '1200', '1300', '2000', '3000', '4000', '5000'];

    public function up(): void
    {
        $accountIds = DB::table('standalone_accounting_accounts')
            ->whereIn('code', self::DEFAULT_CODES)
            ->pluck('id');

        if ($accountIds->isEmpty()) {
            return;
        }

        if (DB::table('standalone_accounting_journal_lines')->whereIn('account_id', $accountIds)->exists()) {
            throw new RuntimeException('Akun default Akuntansi Baru sudah memiliki jurnal dan tidak boleh dinonaktifkan otomatis.');
        }

        DB::table('standalone_accounting_accounts')
            ->whereIn('id', $accountIds)
            ->update([
                'is_active' => false,
                'updated_at' => now(),
            ]);
    }

    public function down(): void
    {
        DB::table('standalone_accounting_accounts')
            ->whereIn('code', self::DEFAULT_CODES)
            ->update([
                'is_active' => true,
                'updated_at' => now(),
            ]);
    }
};
