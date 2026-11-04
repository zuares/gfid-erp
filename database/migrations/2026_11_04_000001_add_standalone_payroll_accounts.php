<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        foreach ([
            ['1-135', 'Piutang Pinjaman Karyawan', 'asset', 'Pinjaman karyawan yang dilunasi melalui payroll atau pembayaran langsung.'],
            ['2-140', 'Hutang Tabungan Karyawan', 'liability', 'Bonus dan tabungan milik karyawan yang belum dibayarkan.'],
        ] as [$code, $name, $type, $description]) {
            DB::table('standalone_accounting_accounts')->insertOrIgnore([
                'code' => $code, 'name' => $name, 'type' => $type, 'description' => $description,
                'is_active' => true, 'created_at' => now(), 'updated_at' => now(),
            ]);
        }
        foreach ([['1308', '1-135', 'Piutang pinjaman karyawan'], ['2104', '2-140', 'Tabungan karyawan']] as [$legacy, $target, $purpose]) {
            $existing = DB::table('standalone_accounting_account_mappings')->where('legacy_account_code', $legacy)->first();
            // Replace only the known temporary consolidation mapping, preserving custom mappings.
            if (! $existing || (str_contains($existing->purpose, 'sementara dikonsolidasikan')
                && in_array($existing->standalone_account_code, ['1-130', '2-120'], true))) {
                DB::table('standalone_accounting_account_mappings')->updateOrInsert(['legacy_account_code' => $legacy], [
                    'standalone_account_code' => $target, 'purpose' => $purpose,
                    'is_active' => true, 'created_at' => now(), 'updated_at' => now(),
                ]);
            }
        }
    }

    public function down(): void
    {
        // COA dan mapping adalah data master. Pertahankan saat rollback agar
        // jurnal historis serta mapping kustom pengguna tidak terhapus.
    }
};
