<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * Menambah akun yang dibutuhkan alur WIP Normalization / Cleanup dan reject.
 *
 * - 1204 Persediaan Barang Cacat  → sudah dipakai JournalService (CODE_INV_DEFECT)
 *   tetapi belum pernah dibuat di COA. Tanpa ini, jurnal reject/defect melempar.
 * - 1205 Persediaan Packaging     → CODE_INV_PACKAGING.
 * - 6115 Selisih Stock Opname     → penampung selisih opname (pengganti 6101 generik).
 * - 6120 Kerugian Produksi/Reject → reject scrap & write-off WIP.
 * - 6116 Koreksi Persediaan Legacy→ khusus close-as-legacy, agar berjejak terpisah.
 *
 * Aditif & idempotent: hanya insert kalau belum ada.
 */
return new class extends Migration
{
    /** @var array<int,array{code:string,name:string,type:string}> */
    private array $accounts = [
        ['code' => '1204', 'name' => 'Persediaan Barang Cacat',        'type' => 'asset'],
        ['code' => '1205', 'name' => 'Persediaan Packaging',           'type' => 'asset'],
        ['code' => '6115', 'name' => 'Selisih Stock Opname',           'type' => 'expense'],
        ['code' => '6116', 'name' => 'Koreksi Persediaan Legacy',      'type' => 'expense'],
        ['code' => '6120', 'name' => 'Kerugian Produksi / Reject',     'type' => 'expense'],
    ];

    public function up(): void
    {
        foreach ($this->accounts as $acc) {
            $exists = DB::table('accounts')->where('code', $acc['code'])->exists();
            if (! $exists) {
                DB::table('accounts')->insert([
                    'code'       => $acc['code'],
                    'name'       => $acc['name'],
                    'type'       => $acc['type'],
                    'is_active'  => 1,
                    'is_cash'    => 0,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }
        }
    }

    public function down(): void
    {
        DB::table('accounts')
            ->whereIn('code', array_column($this->accounts, 'code'))
            ->delete();
    }
};
