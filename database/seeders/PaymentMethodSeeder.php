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
                ->where('code', '1101')
                ->where('is_active', 1)
                ->first();

            $bankJago = Account::query()
                ->where('code', '1111')
                ->where('is_active', 1)
                ->first();

            if (!$kas) {
                throw new \RuntimeException('Account 1101 (Kas Tunai) tidak ditemukan.');
            }

            if (!$bankJago) {
                throw new \RuntimeException('Account 1111 (Bank Jago) tidak ditemukan.');
            }

            // =====================================================
            // PAYMENT METHODS (mode: cash|transfer|credit)
            // =====================================================
            $rows = [

                // ================= CASH =================
                [
                    'code' => 'CASH',
                    'name' => 'Tunai',
                    'mode' => 'cash',
                    'default_cash_account_id' => $kas->id,
                    'sort_order' => 10,
                ],

                // ================= TRANSFER BANK =================
                [
                    'code' => 'BANK',
                    'name' => 'Transfer Bank',
                    'mode' => 'transfer',
                    'default_cash_account_id' => $bankJago->id, // default: Bank Jago (1111)
                    'sort_order' => 20,
                ],

                // ================= CREDIT / TEMPO =================
                [
                    'code' => 'TEMPO',
                    'name' => 'Tempo Supplier',
                    'mode' => 'credit',
                    'default_cash_account_id' => null, // tempo memang tidak pakai kas/bank
                    'sort_order' => 30,
                ],

                // ================= DOWN PAYMENT =================
                // DP tetap "pembayaran" (uang keluar), default transfer via Bank Jago
                // Jika suatu saat DP tunai, user bisa override cash_account_id di form
                [
                    'code' => 'DP',
                    'name' => 'Down Payment',
                    'mode' => 'transfer',
                    'default_cash_account_id' => $bankJago->id,
                    'sort_order' => 40,
                ],
            ];

            foreach ($rows as $r) {
                PaymentMethod::updateOrCreate(
                    ['code' => $r['code']],
                    [
                        'name' => $r['name'],
                        'mode' => $r['mode'],
                        'default_cash_account_id' => $r['default_cash_account_id'],
                        'sort_order' => $r['sort_order'] ?? 0,
                        'is_active' => true,
                    ]
                );
            }
        });
    }
}
