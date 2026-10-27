<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        $now = now();

        foreach ([
            [
                'code' => '4-110',
                'name' => 'Sales / Penjualan',
                'description' => 'Pendapatan kotor dari penjualan barang atau jasa sebelum dikurangi diskon, retur, dan pengurangan penjualan; saldo normalnya kredit.',
            ],
            [
                'code' => '4-120',
                'name' => 'Sales Discount / Diskon Penjualan',
                'description' => 'Pengurang pendapatan karena potongan harga yang diberikan kepada pelanggan; saldo normalnya debit.',
            ],
            [
                'code' => '4-130',
                'name' => 'Sales Return and Allowance / Retur dan Pengurangan Penjualan',
                'description' => 'Pengurang pendapatan karena barang dikembalikan pelanggan atau harga dikurangi setelah penjualan; saldo normalnya debit.',
            ],
        ] as $account) {
            DB::table('standalone_accounting_accounts')->updateOrInsert(
                ['code' => $account['code']],
                [
                    'name' => $account['name'],
                    'description' => $account['description'],
                    'type' => 'revenue',
                    'is_active' => true,
                    'created_at' => $now,
                    'updated_at' => $now,
                ],
            );
        }
    }

    public function down(): void
    {
        DB::table('standalone_accounting_accounts')
            ->whereIn('code', ['4-110', '4-120', '4-130'])
            ->delete();
    }
};
