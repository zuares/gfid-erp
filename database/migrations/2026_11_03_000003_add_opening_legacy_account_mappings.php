<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /** @var array<string, array{target:string, purpose:string}> */
    private const MAPPINGS = [
        '6302' => ['target' => '6-180', 'purpose' => 'Bonus karyawan ke beban gaji dan upah pada COA Akuntansi Baru.'],
        '4101' => ['target' => '4-110', 'purpose' => 'Penjualan ke sales pada COA Akuntansi Baru.'],
        '6303' => ['target' => '6-210', 'purpose' => 'Prive legacy dipertahankan ke beban lain-lain sementara agar mengikuti tipe akun lama; reklasifikasi ekuitas dilakukan terpisah.'],
        '1302' => ['target' => '1-130', 'purpose' => 'Saldo marketplace/clearing sementara dikonsolidasikan ke piutang usaha.'],
        '6304' => ['target' => '6-210', 'purpose' => 'Biaya admin bank sementara dikonsolidasikan ke beban lain-lain.'],
        '6305' => ['target' => '6-210', 'purpose' => 'Koreksi keuangan sementara dikonsolidasikan ke beban lain-lain untuk opening; tinjau bukti koreksi terpisah.'],
        '1306' => ['target' => '1-130', 'purpose' => 'Piutang pinjaman supplier sementara dikonsolidasikan ke piutang usaha.'],
        '6306' => ['target' => '6-210', 'purpose' => 'RnD/pengembangan sementara dikonsolidasikan ke beban lain-lain.'],
        '2104' => ['target' => '2-120', 'purpose' => 'Tabungan karyawan sementara dikonsolidasikan ke beban akrual.'],
        '1307' => ['target' => '1-130', 'purpose' => 'Suspense settlement marketplace sementara dikonsolidasikan ke piutang usaha.'],
        '6301' => ['target' => '6-150', 'purpose' => 'Beban penyusutan peralatan.'],
        '2301' => ['target' => '2-120', 'purpose' => 'Payout marketplace belum direkonsiliasi sementara dikonsolidasikan ke beban akrual.'],
        '1308' => ['target' => '1-130', 'purpose' => 'Piutang pinjaman karyawan sementara dikonsolidasikan ke piutang usaha.'],
    ];

    public function up(): void
    {
        $now = now();

        foreach (self::MAPPINGS as $legacyCode => $mapping) {
            DB::table('standalone_accounting_account_mappings')->updateOrInsert(
                ['legacy_account_code' => $legacyCode],
                [
                    'standalone_account_code' => $mapping['target'],
                    'purpose' => $mapping['purpose'],
                    'is_active' => true,
                    'created_at' => $now,
                    'updated_at' => $now,
                ],
            );
        }
    }

    public function down(): void
    {
        DB::table('standalone_accounting_account_mappings')
            ->whereIn('legacy_account_code', array_keys(self::MAPPINGS))
            ->delete();
    }
};
