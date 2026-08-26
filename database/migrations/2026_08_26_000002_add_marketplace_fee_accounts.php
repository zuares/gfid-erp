<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('accounts')) {
            return;
        }

        foreach ([
            '6202' => 'Biaya Komisi Marketplace',
            '6203' => 'Biaya Layanan Marketplace',
            '6204' => 'Biaya Transaksi Marketplace',
            '6205' => 'Biaya Affiliate Marketplace',
            '6206' => 'Biaya Iklan Marketplace',
            '6207' => 'Biaya Asuransi Pengiriman Marketplace',
        ] as $code => $name) {
            $query = DB::table('accounts')->where('code', $code);
            if ($query->exists()) {
                $query->update(['name' => $name, 'type' => 'expense', 'is_cash' => false, 'is_active' => true, 'updated_at' => now()]);
            } else {
                DB::table('accounts')->insert([
                    'code' => $code,
                    'name' => $name,
                    'type' => 'expense',
                    'is_cash' => false,
                    'is_active' => true,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }
        }
    }

    public function down(): void
    {
        if (! Schema::hasTable('accounts')) {
            return;
        }

        foreach (['6202', '6203', '6204', '6205', '6206', '6207'] as $code) {
            $id = DB::table('accounts')->where('code', $code)->value('id');
            if ($id && ! DB::table('journal_lines')->where('account_id', $id)->exists()) {
                DB::table('accounts')->where('id', $id)->delete();
            }
        }
    }
};
