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
                'code' => '2-110',
                'name' => 'Accounts Payable / Utang Supplier',
                'description' => 'Kewajiban kepada supplier atas pembelian bahan, barang, atau jasa secara kredit yang belum dibayar.',
            ],
            [
                'code' => '2-120',
                'name' => 'Accrued Expenses / Beban Akrual',
                'description' => 'Kewajiban atas listrik, sewa, gaji, atau beban operasional lain yang sudah terjadi tetapi belum ditagihkan atau dibayar.',
            ],
            [
                'code' => '2-130',
                'name' => 'Accrued Production Costs / Biaya Produksi Akrual',
                'description' => 'Kewajiban atas pekerjaan cutting, sewing, finishing, bordir, atau proses produksi lain yang sudah dikerjakan tetapi belum ditagihkan atau dibayar.',
            ],
        ] as $account) {
            DB::table('standalone_accounting_accounts')->updateOrInsert(
                ['code' => $account['code']],
                [
                    'name' => $account['name'],
                    'description' => $account['description'],
                    'type' => 'liability',
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
            ->whereIn('code', ['2-110', '2-120', '2-130'])
            ->delete();
    }
};
