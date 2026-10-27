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
                'code' => '6-110',
                'name' => 'Utilities Expense / Beban Utilitas',
                'description' => 'Beban listrik, air, internet, telepon, dan utilitas lain yang digunakan dalam operasional.',
                'type' => 'expense',
            ],
            [
                'code' => '6-120',
                'name' => 'Office Supplies Expense / Beban Perlengkapan Kantor',
                'description' => 'Beban ATK dan perlengkapan kantor yang digunakan selama periode berjalan.',
                'type' => 'expense',
            ],
            [
                'code' => '6-130',
                'name' => 'Bad Debts Expense / Beban Piutang Tak Tertagih',
                'description' => 'Beban atau kerugian atas piutang pelanggan yang diperkirakan atau dinyatakan tidak tertagih.',
                'type' => 'expense',
            ],
            [
                'code' => '6-140',
                'name' => 'Spoilage Expense / Beban Barang Rusak',
                'description' => 'Beban atas barang rusak atau cacat yang tidak dapat dijual; kerusakan normal produksi dapat direklasifikasi ke biaya produksi.',
                'type' => 'expense',
            ],
            [
                'code' => '6-150',
                'name' => 'Depreciation Expense / Beban Penyusutan',
                'description' => 'Beban penyusutan aset tetap selama periode berjalan.',
                'type' => 'expense',
            ],
            [
                'code' => '6-160',
                'name' => 'Insurance Expense / Beban Asuransi',
                'description' => 'Bagian biaya asuransi yang menjadi beban pada periode berjalan.',
                'type' => 'expense',
            ],
            [
                'code' => '6-170',
                'name' => 'Rent Expense / Beban Sewa',
                'description' => 'Beban sewa gedung, gudang, kantor, atau fasilitas usaha pada periode berjalan.',
                'type' => 'expense',
            ],
            [
                'code' => '6-180',
                'name' => 'Wages and Salaries Expense / Beban Gaji dan Upah',
                'description' => 'Beban gaji dan upah karyawan; upah produksi dapat dipisahkan menjadi biaya produksi bila diperlukan.',
                'type' => 'expense',
            ],
            [
                'code' => '6-190',
                'name' => 'Advertising Expense / Beban Iklan',
                'description' => 'Beban iklan, promosi, pemasaran, dan publikasi usaha.',
                'type' => 'expense',
            ],
            [
                'code' => '6-200',
                'name' => 'Other Operating Expense / Beban Operasional Lainnya',
                'description' => 'Beban operasional yang belum memiliki akun khusus; perlu ditinjau berkala untuk reklasifikasi.',
                'type' => 'expense',
            ],
            [
                'code' => '8-110',
                'name' => 'Interest Income / Pendapatan Bunga',
                'description' => 'Pendapatan bunga dari rekening bank, deposito, atau investasi; bukan pendapatan penjualan utama.',
                'type' => 'revenue',
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
    }

    public function down(): void
    {
        DB::table('standalone_accounting_accounts')
            ->whereIn('code', [
                '6-110', '6-120', '6-130', '6-140', '6-150', '6-160',
                '6-170', '6-180', '6-190', '6-200', '8-110',
            ])
            ->delete();
    }
};
