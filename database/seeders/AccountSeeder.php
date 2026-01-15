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

            // 12xx - Inventory
            ['code' => '1201', 'name' => 'Persediaan Bahan Baku', 'type' => 'asset', 'is_cash' => false],
            ['code' => '1202', 'name' => 'Persediaan WIP', 'type' => 'asset', 'is_cash' => false],
            ['code' => '1203', 'name' => 'Persediaan Barang Jadi', 'type' => 'asset', 'is_cash' => false],

            // =====================================================
            // 2xxx LIABILITY
            // =====================================================
            ['code' => '2101', 'name' => 'Hutang Dagang', 'type' => 'liability', 'is_cash' => false],

            // =====================================================
            // 3xxx EQUITY
            // =====================================================
            ['code' => '3101', 'name' => 'Modal Pemilik', 'type' => 'equity', 'is_cash' => false],
            ['code' => '3201', 'name' => 'Laba Ditahan', 'type' => 'equity', 'is_cash' => false],
            // opsional kalau suatu saat mau pisah ambil pribadi:
            // ['code' => '3301', 'name' => 'Prive Pemilik', 'type' => 'equity', 'is_cash' => false],

            // =====================================================
            // 4xxx REVENUE
            // =====================================================
            ['code' => '4101', 'name' => 'Penjualan (Umum)', 'type' => 'revenue', 'is_cash' => false],
            ['code' => '4111', 'name' => 'Penjualan Shopee', 'type' => 'revenue', 'is_cash' => false],
            ['code' => '4112', 'name' => 'Penjualan TikTok', 'type' => 'revenue', 'is_cash' => false],

            // =====================================================
            // 5xxx COGS (HPP)
            // =====================================================
            // ✅ RULE: gaji borongan / per pcs masuk sini (HPP)
            ['code' => '5101', 'name' => 'HPP', 'type' => 'expense', 'is_cash' => false],

            // =====================================================
            // 6xxx EXPENSE (OPERASIONAL)
            // =====================================================
            // ✅ RULE: gaji harian masuk operasional
            ['code' => '6101', 'name' => 'Biaya Operasional Umum', 'type' => 'expense', 'is_cash' => false],
            ['code' => '6102', 'name' => 'Biaya Listrik', 'type' => 'expense', 'is_cash' => false],
            ['code' => '6103', 'name' => 'Biaya Internet', 'type' => 'expense', 'is_cash' => false],
            ['code' => '6104', 'name' => 'Biaya Transport / Ongkir', 'type' => 'expense', 'is_cash' => false],
            ['code' => '6105', 'name' => 'Biaya Gaji (Harian / Operasional)', 'type' => 'expense', 'is_cash' => false],

            // 62xx - Marketplace Fees
            ['code' => '6201', 'name' => 'Biaya Admin Marketplace', 'type' => 'expense', 'is_cash' => false],
            ['code' => '6202', 'name' => 'Biaya Layanan Marketplace', 'type' => 'expense', 'is_cash' => false],
            ['code' => '6203', 'name' => 'Biaya Proses Pesanan', 'type' => 'expense', 'is_cash' => false],
            ['code' => '6204', 'name' => 'Komisi Marketplace (AMS)', 'type' => 'expense', 'is_cash' => false],
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

            // Default aman: jangan nonaktifkan akun lain (biar tidak ganggu akun custom)
            // Kalau mau rapihin COA supaya hanya ini yang aktif, aktifkan blok ini:
            // $codes = collect($accounts)->pluck('code')->all();
            // Account::whereNotIn('code', $codes)->update(['is_active' => false]);
        });
    }
}
