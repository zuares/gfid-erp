<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    private const NEW_INVENTORY_CODES = ['1-151', '1-152', '1-153'];

    public function up(): void
    {
        $now = now();

        foreach ([
            [
                'code' => '1-150',
                'name' => 'Raw Materials Inventory / Persediaan Bahan dan Material',
                'description' => 'Nilai bahan baku dan material produksi yang masih tersedia. Saldo opening lama tidak direklasifikasi oleh migration ini.',
                'type' => 'asset',
            ],
            [
                'code' => '1-151',
                'name' => 'Work in Process Inventory / Persediaan Dalam Proses (WIP)',
                'description' => 'Nilai bahan, tenaga kerja, dan biaya produksi yang masih berada dalam proses sebelum menjadi barang jadi.',
                'type' => 'asset',
            ],
            [
                'code' => '1-152',
                'name' => 'Finished Goods Inventory / Persediaan Barang Jadi',
                'description' => 'Nilai barang jadi yang telah selesai diproduksi dan belum dibebankan sebagai HPP penjualan.',
                'type' => 'asset',
            ],
            [
                'code' => '1-153',
                'name' => 'Rejected Goods Inventory / Persediaan Barang Reject atau Cacat',
                'description' => 'Nilai barang reject atau cacat yang masih disimpan sebagai persediaan dan belum dihapuskan atau dibebankan sebagai spoilage.',
                'type' => 'asset',
            ],
            [
                'code' => '2-130',
                'name' => 'Accrued Production Costs / Biaya Produksi Akrual',
                'description' => 'Kewajiban atas pekerjaan produksi yang sudah terjadi tetapi belum ditagihkan atau dibayar.',
                'type' => 'liability',
            ],
            [
                'code' => '5-110',
                'name' => 'Cost of Goods Sold / Harga Pokok Penjualan',
                'description' => 'Biaya perolehan atau biaya produksi barang yang sudah terjual.',
                'type' => 'expense',
            ],
            [
                'code' => '6-140',
                'name' => 'Spoilage Expense / Beban Barang Rusak',
                'description' => 'Beban atas barang produksi yang rusak, scrap, atau tidak lagi diakui sebagai persediaan.',
                'type' => 'expense',
            ],
        ] as $account) {
            DB::table('standalone_accounting_accounts')->updateOrInsert(
                ['code' => $account['code']],
                [
                    'name' => $account['name'],
                    'description' => $account['description'],
                    'type' => $account['type'],
                    'is_active' => true,
                    'created_at' => $now,
                    'updated_at' => $now,
                ],
            );
        }

        foreach ([
            ['1201', '1-150', 'Persediaan bahan/material produksi'],
            ['1202', '1-151', 'Persediaan WIP produksi'],
            ['1203', '1-152', 'Persediaan barang jadi produksi'],
            ['1204', '1-153', 'Persediaan barang reject/cacat produksi'],
            ['2102', '2-130', 'Hutang atau akrual biaya produksi'],
            ['5101', '5-110', 'HPP barang yang terjual'],
            ['6120', '6-140', 'Kerugian produksi, reject, atau spoilage'],
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
        $accountIds = DB::table('standalone_accounting_accounts')
            ->whereIn('code', self::NEW_INVENTORY_CODES)
            ->pluck('id');

        if ($accountIds->isNotEmpty()
            && DB::table('standalone_accounting_journal_lines')->whereIn('account_id', $accountIds)->exists()) {
            throw new RuntimeException('COA produksi sudah dipakai jurnal dan tidak boleh dihapus saat rollback.');
        }

        $now = now();

        foreach (['1201', '1202', '1203', '1204'] as $legacyCode) {
            DB::table('standalone_accounting_account_mappings')->updateOrInsert(
                ['legacy_account_code' => $legacyCode],
                [
                    'standalone_account_code' => '1-150',
                    'purpose' => 'Persediaan ke persediaan baru',
                    'is_active' => true,
                    'created_at' => $now,
                    'updated_at' => $now,
                ],
            );
        }

        DB::table('standalone_accounting_account_mappings')
            ->whereIn('legacy_account_code', ['2102', '5101'])
            ->delete();

        DB::table('standalone_accounting_accounts')
            ->where('code', '1-150')
            ->update([
                'name' => 'Merchandise Inventory / Persediaan',
                'description' => 'Nilai bahan, barang dalam proses, atau barang jadi yang masih dimiliki perusahaan dan belum dibebankan sebagai HPP.',
                'updated_at' => $now,
            ]);

        DB::table('standalone_accounting_accounts')
            ->whereIn('code', self::NEW_INVENTORY_CODES)
            ->delete();
    }
};
