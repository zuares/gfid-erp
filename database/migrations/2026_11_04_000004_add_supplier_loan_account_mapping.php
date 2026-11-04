<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        $now = now();

        DB::table('standalone_accounting_accounts')->insertOrIgnore([
            'code' => '1-136',
            'name' => 'Piutang Pinjaman Supplier',
            'type' => 'asset',
            'description' => 'Dana yang dipinjamkan kepada supplier dan belum dikembalikan atau dialokasikan ke pembelian.',
            'is_active' => true,
            'created_at' => $now,
            'updated_at' => $now,
        ]);

        $existing = DB::table('standalone_accounting_account_mappings')
            ->where('legacy_account_code', '1306')
            ->first();

        // Replace only the known temporary opening consolidation. Preserve a
        // mapping that has been customized by an operator.
        if (! $existing || (
            $existing->standalone_account_code === '1-130'
            && str_contains((string) $existing->purpose, 'sementara dikonsolidasikan')
        )) {
            DB::table('standalone_accounting_account_mappings')->updateOrInsert(
                ['legacy_account_code' => '1306'],
                [
                    'standalone_account_code' => '1-136',
                    'purpose' => 'Piutang pinjaman supplier',
                    'is_active' => true,
                    'created_at' => $now,
                    'updated_at' => $now,
                ],
            );
        }
    }

    public function down(): void
    {
        // COA dan mapping dipertahankan saat rollback agar histori jurnal dan
        // mapping kustom tidak terhapus.
    }
};
