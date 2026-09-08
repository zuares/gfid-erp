<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        $now = now();

        DB::table('payment_methods')->updateOrInsert(
            ['code' => 'LOAN_APPLY'],
            [
                'name' => 'Alokasi Pinjaman Supplier',
                'mode' => 'credit',
                'description' => 'Alokasi saldo pinjaman supplier menjadi uang muka PO',
                'sort_order' => 91,
                'is_active' => true,
                'default_cash_account_id' => null,
                'updated_at' => $now,
                'created_at' => $now,
            ]
        );
    }

    public function down(): void
    {
        DB::table('payment_methods')->where('code', 'LOAN_APPLY')->delete();
    }
};
