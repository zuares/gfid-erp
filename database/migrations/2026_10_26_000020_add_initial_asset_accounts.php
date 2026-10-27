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
                'code' => '1-110',
                'name' => 'Cash in Bank / Kas di Bank',
                'description' => 'Saldo dana perusahaan yang tersimpan pada rekening bank. Gunakan rincian per bank bila diperlukan untuk rekonsiliasi.',
                'type' => 'asset',
            ],
            [
                'code' => '1-120',
                'name' => 'Petty Cash / Kas Kecil',
                'description' => 'Uang tunai yang disediakan untuk pengeluaran operasional kecil dan rutin.',
                'type' => 'asset',
            ],
            [
                'code' => '1-130',
                'name' => 'Accounts Receivable / Piutang Usaha',
                'description' => 'Tagihan kepada pelanggan atas penjualan kredit yang belum diterima pembayarannya.',
                'type' => 'asset',
            ],
            [
                'code' => '1-140',
                'name' => 'Allowance for Bad Debt / Cadangan Piutang Tak Tertagih',
                'description' => 'Akun kontra-aset untuk mengurangi nilai piutang yang diperkirakan tidak tertagih; saldo normalnya kredit.',
                'type' => 'asset',
            ],
            [
                'code' => '1-150',
                'name' => 'Merchandise Inventory / Persediaan',
                'description' => 'Nilai bahan, barang dalam proses, atau barang jadi yang masih dimiliki perusahaan dan belum dibebankan sebagai HPP.',
                'type' => 'asset',
            ],
            [
                'code' => '1-160',
                'name' => 'Office Supplies / Perlengkapan Kantor',
                'description' => 'Nilai perlengkapan kantor yang masih tersedia dan belum digunakan sampai akhir periode.',
                'type' => 'asset',
            ],
            [
                'code' => '1-170',
                'name' => 'Prepaid Insurance / Asuransi Dibayar di Muka',
                'description' => 'Bagian premi asuransi yang sudah dibayar tetapi manfaat perlindungannya belum seluruhnya menjadi beban.',
                'type' => 'asset',
            ],
            [
                'code' => '1-192',
                'name' => 'Prepaid Income Tax Article 25 / Pajak Penghasilan Dibayar di Muka Pasal 25',
                'description' => 'Pembayaran angsuran PPh Pasal 25 yang dapat diperhitungkan sebagai kredit pajak, berdasarkan bukti dan pencatatan pajak yang valid.',
                'type' => 'asset',
            ],
            [
                'code' => '1-193',
                'name' => 'Investment / Investasi',
                'description' => 'Penempatan dana atau kepemilikan investasi perusahaan yang bukan merupakan kas operasional sehari-hari; klasifikasi jangka pendek atau panjang perlu dikonfirmasi.',
                'type' => 'asset',
            ],
            [
                'code' => '1-310',
                'name' => 'Land / Tanah',
                'description' => 'Nilai perolehan tanah yang dimiliki perusahaan. Tanah pada umumnya tidak disusutkan.',
                'type' => 'asset',
            ],
            [
                'code' => '1-320',
                'name' => 'Buildings / Bangunan',
                'description' => 'Nilai perolehan bangunan yang digunakan untuk kegiatan usaha sebelum dikurangi akumulasi penyusutan.',
                'type' => 'asset',
            ],
            [
                'code' => '1-321',
                'name' => 'Accumulated Depreciation — Buildings / Akumulasi Penyusutan Bangunan',
                'description' => 'Akun kontra-aset yang menampung total penyusutan bangunan sampai tanggal laporan; saldo normalnya kredit.',
                'type' => 'asset',
            ],
            [
                'code' => '1-330',
                'name' => 'Equipment / Peralatan',
                'description' => 'Nilai perolehan mesin dan peralatan yang digunakan dalam operasional sebelum dikurangi akumulasi penyusutan.',
                'type' => 'asset',
            ],
            [
                'code' => '1-331',
                'name' => 'Accumulated Depreciation — Equipment / Akumulasi Penyusutan Peralatan',
                'description' => 'Akun kontra-aset yang menampung total penyusutan peralatan sampai tanggal laporan; saldo normalnya kredit.',
                'type' => 'asset',
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
                '1-110', '1-120', '1-130', '1-140', '1-150', '1-160', '1-170',
                '1-192', '1-193', '1-310', '1-320', '1-321', '1-330', '1-331',
            ])
            ->delete();
    }
};
