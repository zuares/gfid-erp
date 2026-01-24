<?php

namespace Database\Seeders;

use App\Models\Account;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class AccountSeeder extends Seeder
{
    public function run(): void
    {
        /**
         * PRINSIP:
         * - code = kontrak sistem (dipakai service)
         * - name = deskriptif & manusiawi
         * - type = asset | liability | equity | revenue | expense
         */

        $accounts = [

            // ==================================================
            // 1xxx ASSET
            // ==================================================

            // Cash & Bank
            ['code' => '1101', 'name' => 'Kas Tunai', 'type' => 'asset', 'is_cash' => true],
            ['code' => '1111', 'name' => 'Bank Jago (Bisnis)', 'type' => 'asset', 'is_cash' => true],
            ['code' => '1112', 'name' => 'Bank BCA (Operasional)', 'type' => 'asset', 'is_cash' => true],

            // Inventory
            ['code' => '1201', 'name' => 'Persediaan Bahan Baku', 'type' => 'asset'],
            ['code' => '1202', 'name' => 'Persediaan WIP', 'type' => 'asset'],
            ['code' => '1203', 'name' => 'Persediaan Barang Jadi', 'type' => 'asset'],

            // Receivable
            ['code' => '1301', 'name' => 'Piutang Dagang', 'type' => 'asset'],
            ['code' => '1302', 'name' => 'Piutang Marketplace', 'type' => 'asset'],

            // Tax
            ['code' => '1401', 'name' => 'PPN Masukan', 'type' => 'asset'],

            // Advance
            ['code' => '1151', 'name' => 'Uang Muka Pembelian', 'type' => 'asset'],

            // ==================================================
            // 2xxx LIABILITY
            // ==================================================
            ['code' => '2101', 'name' => 'Hutang Dagang', 'type' => 'liability'],

            // ==================================================
            // 3xxx EQUITY
            // ==================================================
            ['code' => '3101', 'name' => 'Modal Pemilik', 'type' => 'equity'],
            ['code' => '3301', 'name' => 'Prive Pemilik', 'type' => 'equity'],

            // ==================================================
            // 4xxx REVENUE
            // ==================================================
            ['code' => '4101', 'name' => 'Penjualan', 'type' => 'revenue'],
            ['code' => '4201', 'name' => 'Retur Penjualan', 'type' => 'revenue'], // contra

            // ==================================================
            // 5xxx COGS / HPP
            // ==================================================
            ['code' => '5101', 'name' => 'Harga Pokok Penjualan (HPP)', 'type' => 'expense'],

            // ==================================================
            // 6xxx EXPENSE OPERASIONAL
            // ==================================================
            ['code' => '6101', 'name' => 'Biaya Operasional Umum', 'type' => 'expense'],
            ['code' => '6102', 'name' => 'Biaya Transport / Ongkir', 'type' => 'expense'],
            ['code' => '6103', 'name' => 'Biaya Gaji Operasional', 'type' => 'expense'],
            ['code' => '6104', 'name' => 'Biaya ATK', 'type' => 'expense'],
            ['code' => '6110', 'name' => 'Biaya Packing', 'type' => 'expense'],

            // Marketplace
            ['code' => '6201', 'name' => 'Biaya Marketplace', 'type' => 'expense'],
        ];

        DB::transaction(function () use ($accounts) {
            foreach ($accounts as $acc) {
                Account::updateOrCreate(
                    ['code' => $acc['code']],
                    [
                        'name' => $acc['name'],
                        'type' => $acc['type'],
                        'is_cash' => (bool) ($acc['is_cash'] ?? false),
                        'is_active' => true,
                    ]
                );
            }
        });
    }
}
