<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    private const CODES = ['1301', '1501', '1502', '1503', '1591'];

    public function up(): void
    {
        $now = now();

        foreach ([
            ['1301', '1-130', 'Piutang dagang ke Accounts Receivable'],
            ['1501', '1-330', 'Peralatan produksi dikonsolidasikan ke Equipment'],
            ['1502', '1-330', 'Peralatan kantor dikonsolidasikan ke Equipment'],
            ['1503', '1-330', 'Peralatan packing dikonsolidasikan ke Equipment'],
            ['1591', '1-331', 'Akumulasi penyusutan peralatan'],
        ] as [$legacyCode, $standaloneCode, $purpose]) {
            DB::table('standalone_accounting_account_mappings')->updateOrInsert(
                ['legacy_account_code' => $legacyCode],
                [
                    'standalone_account_code' => $standaloneCode,
                    'purpose' => $purpose,
                    'is_active' => true,
                    'created_at' => $now,
                    'updated_at' => $now,
                ],
            );
        }
    }

    public function down(): void
    {
        DB::table('standalone_accounting_account_mappings')
            ->whereIn('legacy_account_code', self::CODES)
            ->delete();
    }
};
