<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        $now = now();

        DB::table('standalone_accounting_accounts')->updateOrInsert(
            ['code' => '1-191'],
            [
                'name' => 'VAT Input / PPN Masukan',
                'description' => 'PPN masukan yang timbul dari penerimaan pembelian dan dapat direkonsiliasi dengan dokumen pajak.',
                'type' => 'asset',
                'is_active' => true,
                'created_at' => $now,
                'updated_at' => $now,
            ],
        );

        foreach ([
            ['1201', '1-150', 'Persediaan bahan baku ke persediaan baru'],
            ['1202', '1-150', 'Persediaan WIP ke persediaan baru'],
            ['1203', '1-150', 'Persediaan barang jadi ke persediaan baru'],
            ['1204', '1-150', 'Persediaan barang cacat ke persediaan baru'],
            ['1205', '1-150', 'Persediaan packaging ke persediaan baru'],
            ['1210', '1-150', 'Persediaan perlengkapan ke persediaan baru'],
            ['1305', '1-130', 'Piutang supplier / klaim supplier ke piutang usaha baru'],
            ['1401', '1-191', 'PPN masukan'],
            ['2101', '2-110', 'Hutang supplier'],
            ['6101', '6-210', 'Biaya operasional umum ke beban lain-lain baru'],
            ['6102', '6-210', 'Biaya transport / ongkir ke beban lain-lain baru'],
            ['6103', '6-180', 'Biaya gaji operasional'],
            ['6104', '6-120', 'Biaya ATK ke beban perlengkapan kantor'],
            ['6105', '6-210', 'Biaya pemeliharaan mesin ke beban lain-lain baru'],
            ['6110', '6-210', 'Biaya packing ke beban lain-lain baru'],
            ['6112', '6-120', 'Beban perlengkapan'],
            ['6115', '6-210', 'Selisih stock opname ke beban lain-lain baru'],
            ['6116', '6-210', 'Koreksi persediaan legacy ke beban lain-lain baru'],
            ['6120', '6-140', 'Kerugian produksi / reject ke beban barang rusak'],
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
        $codes = [
            '1201', '1202', '1203', '1204', '1205', '1210', '1305', '1401',
            '6101', '6102', '6103', '6104', '6105', '6110', '6112',
            '6115', '6116', '6120',
        ];

        DB::table('standalone_accounting_account_mappings')
            ->whereIn('legacy_account_code', $codes)
            ->delete();

        $accountId = DB::table('standalone_accounting_accounts')
            ->where('code', '1-191')
            ->value('id');

        if ($accountId && DB::table('standalone_accounting_journal_lines')->where('account_id', $accountId)->exists()) {
            throw new RuntimeException('Akun 1-191 sudah dipakai jurnal dan tidak boleh dihapus saat rollback.');
        }

        DB::table('standalone_accounting_accounts')->where('code', '1-191')->delete();
    }
};
