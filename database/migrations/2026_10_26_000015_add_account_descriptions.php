<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('standalone_accounting_accounts', function (Blueprint $table) {
            $table->text('description')->nullable();
        });

        foreach ([
            '1000' => 'Uang tunai fisik yang disimpan di kas perusahaan.',
            '1100' => 'Saldo rekening bank perusahaan. Gunakan akun detail jika nanti dibuat per bank.',
            '1200' => 'Tagihan kepada pelanggan yang belum diterima pembayarannya.',
            '1300' => 'Nilai bahan atau barang yang masih menjadi persediaan perusahaan.',
            '2000' => 'Kewajiban kepada supplier atas pembelian secara kredit.',
            '3000' => 'Setoran dan hak pemilik atas aset bersih perusahaan.',
            '4000' => 'Pendapatan dari penjualan barang atau jasa perusahaan.',
            '5000' => 'Biaya yang dikeluarkan atau menjadi kewajiban untuk menjalankan usaha.',
        ] as $code => $description) {
            DB::table('standalone_accounting_accounts')
                ->where('code', $code)
                ->update(['description' => $description]);
        }
    }

    public function down(): void
    {
        Schema::table('standalone_accounting_accounts', function (Blueprint $table) {
            $table->dropColumn('description');
        });
    }
};
