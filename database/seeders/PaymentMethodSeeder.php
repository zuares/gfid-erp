<?php
namespace Database\Seeders;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class PaymentMethodSeeder extends Seeder
{
    public function run(): void
    {
            DB::table('payment_methods')->updateOrInsert(['code' => 'CASH'], [
                'code' => 'CASH',
                'name' => 'Tunai',
                'mode' => 'cash',
                'description' => 'Pembayaran tunai',
                'sort_order' => 10,
                'is_active' => 1,
                'default_cash_account_id' => 1,
            ]);
            DB::table('payment_methods')->updateOrInsert(['code' => 'BANK'], [
                'code' => 'BANK',
                'name' => 'Transfer Bank',
                'mode' => 'transfer',
                'description' => 'Pembayaran via transfer bank',
                'sort_order' => 20,
                'is_active' => 1,
                'default_cash_account_id' => 2,
            ]);
            DB::table('payment_methods')->updateOrInsert(['code' => 'TEMPO'], [
                'code' => 'TEMPO',
                'name' => 'Tempo Supplier',
                'mode' => 'credit',
                'description' => 'Pembelian tempo / hutang supplier',
                'sort_order' => 30,
                'is_active' => 1,
                'default_cash_account_id' => null,
            ]);
            DB::table('payment_methods')->updateOrInsert(['code' => 'DP_APPLY'], [
                'code' => 'DP_APPLY',
                'name' => 'Offset DP',
                'mode' => 'credit',
                'description' => 'Offset uang muka (DP) ke hutang supplier',
                'sort_order' => 90,
                'is_active' => 1,
                'default_cash_account_id' => null,
            ]);
    }
}
