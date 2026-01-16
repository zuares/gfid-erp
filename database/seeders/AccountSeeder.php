<?php

namespace Database\Seeders;

use App\Models\Account;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class AccountSeeder extends Seeder
{
    public function run(): void
    {
        $accounts = [
            // =====================================================
            // 1xxx ASSET
            // =====================================================

            // 11xx - Cash & Bank
            ['code' => '1101', 'name' => 'Kas Tunai', 'type' => 'asset', 'is_cash' => true],
            ['code' => '1111', 'name' => 'Bank Jago (Bisnis)', 'type' => 'asset', 'is_cash' => true],
            ['code' => '1112', 'name' => 'Bank BCA (Transit TikTok / Tarik Bahan)', 'type' => 'asset', 'is_cash' => true],
            ['code' => '1113', 'name' => 'Bank SeaBank (Transit Shopee)', 'type' => 'asset', 'is_cash' => true],
            ['code' => '1114', 'name' => 'DANA (Transit Gaji)', 'type' => 'asset', 'is_cash' => true],

            // (Opsional) kalau kamu butuh rekening operasional terpisah dari transit
            ['code' => '1121', 'name' => 'Bank BCA (Operasional)', 'type' => 'asset', 'is_cash' => true],

            // 115x - Prepaid / Advance
            ['code' => '1151', 'name' => 'Uang Muka Pembelian', 'type' => 'asset', 'is_cash' => false],
            ['code' => '1152', 'name' => 'Uang Muka Operasional', 'type' => 'asset', 'is_cash' => false],

            // 12xx - Inventory
            ['code' => '1201', 'name' => 'Persediaan Bahan Baku', 'type' => 'asset', 'is_cash' => false],
            ['code' => '1202', 'name' => 'Persediaan WIP', 'type' => 'asset', 'is_cash' => false],
            ['code' => '1203', 'name' => 'Persediaan Barang Jadi', 'type' => 'asset', 'is_cash' => false],

            // 13xx - Receivable
            ['code' => '1301', 'name' => 'Piutang Dagang', 'type' => 'asset', 'is_cash' => false],
            ['code' => '1302', 'name' => 'Piutang Marketplace (Shopee/TikTok)', 'type' => 'asset', 'is_cash' => false],

            // 14xx - Tax Asset
            ['code' => '1401', 'name' => 'PPN Masukan', 'type' => 'asset', 'is_cash' => false],

            // 16xx - Fixed Assets
            ['code' => '1601', 'name' => 'Peralatan & Inventaris', 'type' => 'asset', 'is_cash' => false],
            ['code' => '1602', 'name' => 'Akumulasi Penyusutan', 'type' => 'asset', 'is_cash' => false], // contra asset (credit normal)

            // =====================================================
            // 2xxx LIABILITY
            // =====================================================
            ['code' => '2101', 'name' => 'Hutang Dagang', 'type' => 'liability', 'is_cash' => false],
            ['code' => '2102', 'name' => 'Hutang Upah Borongan (PCS)', 'type' => 'liability', 'is_cash' => false],

            // Pajak
            ['code' => '2201', 'name' => 'PPN Keluaran', 'type' => 'liability', 'is_cash' => false],
            ['code' => '2202', 'name' => 'Hutang Pajak Lainnya', 'type' => 'liability', 'is_cash' => false],

            // Accrued / lainnya
            ['code' => '2301', 'name' => 'Hutang Biaya (Accrued Expenses)', 'type' => 'liability', 'is_cash' => false],
            ['code' => '2401', 'name' => 'Hutang Gaji', 'type' => 'liability', 'is_cash' => false],

            // =====================================================
            // 3xxx EQUITY
            // =====================================================
            ['code' => '3101', 'name' => 'Modal Pemilik', 'type' => 'equity', 'is_cash' => false],
            ['code' => '3201', 'name' => 'Laba Ditahan', 'type' => 'equity', 'is_cash' => false],
            ['code' => '3301', 'name' => 'Prive Pemilik', 'type' => 'equity', 'is_cash' => false],

            // =====================================================
            // 4xxx REVENUE
            // =====================================================
            ['code' => '4101', 'name' => 'Penjualan (Umum)', 'type' => 'revenue', 'is_cash' => false],
            ['code' => '4111', 'name' => 'Penjualan Shopee', 'type' => 'revenue', 'is_cash' => false],
            ['code' => '4112', 'name' => 'Penjualan TikTok', 'type' => 'revenue', 'is_cash' => false],
            ['code' => '4201', 'name' => 'Retur Penjualan', 'type' => 'revenue', 'is_cash' => false], // contra revenue
            ['code' => '4301', 'name' => 'Pendapatan Lain-lain', 'type' => 'revenue', 'is_cash' => false],

            // =====================================================
            // 5xxx COGS / HPP (type expense)
            // =====================================================
            ['code' => '5101', 'name' => 'HPP - Bahan Baku', 'type' => 'expense', 'is_cash' => false],
            ['code' => '5102', 'name' => 'HPP - Upah Produksi (Borongan)', 'type' => 'expense', 'is_cash' => false],
            ['code' => '5103', 'name' => 'HPP - Overhead Produksi', 'type' => 'expense', 'is_cash' => false],

            // =====================================================
            // 6xxx EXPENSE (OPERASIONAL)
            // =====================================================
            ['code' => '6101', 'name' => 'Biaya Operasional Umum', 'type' => 'expense', 'is_cash' => false],
            ['code' => '6102', 'name' => 'Biaya Listrik', 'type' => 'expense', 'is_cash' => false],
            ['code' => '6103', 'name' => 'Biaya Internet', 'type' => 'expense', 'is_cash' => false],
            ['code' => '6104', 'name' => 'Biaya Transport / Ongkir', 'type' => 'expense', 'is_cash' => false],
            ['code' => '6105', 'name' => 'Biaya Gaji (Harian / Operasional)', 'type' => 'expense', 'is_cash' => false],
            ['code' => '6106', 'name' => 'Biaya Sewa', 'type' => 'expense', 'is_cash' => false],
            ['code' => '6107', 'name' => 'Biaya Maintenance', 'type' => 'expense', 'is_cash' => false],
            ['code' => '6108', 'name' => 'Biaya ATK', 'type' => 'expense', 'is_cash' => false],
            ['code' => '6109', 'name' => 'Biaya Penyusutan', 'type' => 'expense', 'is_cash' => false],

            // 62xx - Marketplace Fees
            ['code' => '6201', 'name' => 'Biaya Admin Marketplace', 'type' => 'expense', 'is_cash' => false],
            ['code' => '6202', 'name' => 'Biaya Layanan Marketplace', 'type' => 'expense', 'is_cash' => false],
            ['code' => '6203', 'name' => 'Biaya Proses Pesanan', 'type' => 'expense', 'is_cash' => false],
            ['code' => '6204', 'name' => 'Komisi Marketplace (AMS)', 'type' => 'expense', 'is_cash' => false],

            // opsional per-channel (kalau kamu mau lebih detail)
            ['code' => '6211', 'name' => 'Biaya Admin Shopee', 'type' => 'expense', 'is_cash' => false],
            ['code' => '6212', 'name' => 'Biaya Admin TikTok', 'type' => 'expense', 'is_cash' => false],
        ];

        DB::transaction(function () use ($accounts) {
            foreach ($accounts as $acc) {
                Account::updateOrCreate(
                    ['code' => $acc['code']],
                    [
                        'name' => $acc['name'],
                        'type' => $acc['type'],
                        'is_cash' => (bool) $acc['is_cash'],
                        'is_active' => true,
                    ]
                );
            }
        });
    }
}
