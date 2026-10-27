<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        $now = now();

        DB::table('standalone_accounting_accounts')->updateOrInsert(
            ['code' => '5-110'],
            [
                'name' => 'Cost of Goods Sold / Harga Pokok Penjualan',
                'description' => 'Biaya perolehan atau biaya produksi barang yang sudah terjual; saldo normalnya debit. HPP per unit dicatat sebagai dasar perhitungan, bukan sebagai saldo akun langsung.',
                'type' => 'expense',
                'is_active' => true,
                'created_at' => $now,
                'updated_at' => $now,
            ],
        );
    }

    public function down(): void
    {
        DB::table('standalone_accounting_accounts')
            ->where('code', '5-110')
            ->delete();
    }
};
