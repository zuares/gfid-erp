<?php

namespace Database\Seeders;

use App\Models\Account;
use App\Models\PaymentMethod;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class PaymentMethodSeeder extends Seeder
{
    public function run(): void
    {
        DB::transaction(function () {

            // =====================================================
            // Ambil akun kas / bank (sesuai COA)
            // =====================================================
            $kas = Account::query()
                ->where('code', '1101') // Kas Tunai
                ->where('is_active', 1)
                ->first();

            $bankJago = Account::query()
                ->where('code', '1111') // Bank Jago
                ->where('is_active', 1)
                ->first();

            if (!$kas) {
                throw new \RuntimeException('Account 1101 (Kas Tunai) tidak ditemukan.');
            }

            if (!$bankJago) {
                throw new \RuntimeException('Account 1111 (Bank Jago) tidak ditemukan.');
            }

            // =====================================================
            // PAYMENT METHODS
            // mode: cash | transfer | credit
            // =====================================================
            $rows = [
                // -------------------------------------------------
                // CASH
                // -------------------------------------------------
                [
                    'code' => 'CASH',
                    'name' => 'Tunai',
                    'mode' => 'cash',
                    'description' => 'Pembayaran tunai',
                    'default_cash_account_id' => $kas->id,
                    'sort_order' => 10,
                ],

                // -------------------------------------------------
                // BANK TRANSFER
                // -------------------------------------------------
                [
                    'code' => 'BANK',
                    'name' => 'Transfer Bank',
                    'mode' => 'transfer',
                    'description' => 'Pembayaran via transfer bank',
                    'default_cash_account_id' => $bankJago->id,
                    'sort_order' => 20,
                ],

                // -------------------------------------------------
                // TEMPO / CREDIT (tidak bikin jurnal kas)
                // -------------------------------------------------
                [
                    'code' => 'TEMPO',
                    'name' => 'Tempo Supplier',
                    'mode' => 'credit',
                    'description' => 'Pembelian tempo / hutang supplier',
                    'default_cash_account_id' => null,
                    'sort_order' => 30,
                ],

                // -------------------------------------------------
                // DP APPLY / OFFSET DP (TANPA KAS/BANK)
                // -------------------------------------------------
                [
                    'code' => 'DP_APPLY',
                    'name' => 'Offset DP',
                    'mode' => 'credit',
                    'description' => 'Offset uang muka (DP) ke hutang supplier',
                    'default_cash_account_id' => null,
                    'sort_order' => 90,
                ],
            ];

            foreach ($rows as $r) {
                PaymentMethod::updateOrCreate(
                    ['code' => $r['code']],
                    [
                        'name' => $r['name'],
                        'mode' => $r['mode'],
                        'description' => $r['description'] ?? null,
                        'default_cash_account_id' => $r['default_cash_account_id'],
                        'sort_order' => $r['sort_order'] ?? 0,
                        'is_active' => true,
                    ]
                );
            }
        });
    }
}
