<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    private const METHODS = [
        [
            'code' => 'CASH',
            'name' => 'Tunai',
            'mode' => 'cash',
            'description' => 'Pembayaran tunai melalui akun Kas Tunai.',
            'sort_order' => 10,
            'cash_code' => '1101',
        ],
        [
            'code' => 'BANK',
            'name' => 'Transfer Bank',
            'mode' => 'transfer',
            'description' => 'Pembayaran melalui rekening bank atau e-wallet.',
            'sort_order' => 20,
            'cash_code' => '1111',
        ],
        [
            'code' => 'TEMPO',
            'name' => 'Tempo Supplier',
            'mode' => 'credit',
            'description' => 'DP tempo atau pembelian kredit supplier.',
            'sort_order' => 30,
            'cash_code' => null,
        ],
        [
            'code' => 'DP_APPLY',
            'name' => 'Offset DP',
            'mode' => 'credit',
            'description' => 'Offset uang muka ke hutang supplier setelah GRN POSTED.',
            'sort_order' => 90,
            'cash_code' => null,
        ],
    ];

    public function up(): void
    {
        $now = now();

        foreach (self::METHODS as $method) {
            $cashAccountId = $method['cash_code']
                ? DB::table('accounts')->where('code', $method['cash_code'])->value('id')
                : null;

            DB::table('payment_methods')->updateOrInsert(
                ['code' => $method['code']],
                [
                    'name' => $method['name'],
                    'mode' => $method['mode'],
                    'description' => $method['description'],
                    'sort_order' => $method['sort_order'],
                    'is_active' => true,
                    'default_cash_account_id' => $cashAccountId,
                    'updated_at' => $now,
                    'created_at' => $now,
                ],
            );
        }
    }

    public function down(): void
    {
        DB::table('payment_methods')
            ->whereIn('code', ['CASH', 'BANK', 'TEMPO', 'DP_APPLY'])
            ->delete();
    }
};
