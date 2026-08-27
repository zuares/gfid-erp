<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    private array $accounts = [
        ['code' => '1210', 'name' => 'Persediaan Perlengkapan',             'type' => 'asset'],
        ['code' => '1501', 'name' => 'Peralatan Produksi',                  'type' => 'asset'],
        ['code' => '1502', 'name' => 'Peralatan Kantor',                    'type' => 'asset'],
        ['code' => '1503', 'name' => 'Peralatan Packing',                   'type' => 'asset'],
        ['code' => '1591', 'name' => 'Akumulasi Penyusutan Peralatan',      'type' => 'asset'],
        ['code' => '6112', 'name' => 'Beban Perlengkapan',                  'type' => 'expense'],
        ['code' => '6301', 'name' => 'Beban Penyusutan Peralatan',          'type' => 'expense'],
    ];

    public function up(): void
    {
        if (! Schema::hasTable('accounts')) {
            return;
        }

        foreach ($this->accounts as $account) {
            DB::table('accounts')->updateOrInsert(
                ['code' => $account['code']],
                $account + [
                    'is_cash' => false,
                    'is_active' => true,
                    'updated_at' => now(),
                ] + (DB::table('accounts')->where('code', $account['code'])->exists() ? [] : [
                    'created_at' => now(),
                ])
            );
        }
    }

    public function down(): void
    {
        if (! Schema::hasTable('accounts')) {
            return;
        }

        foreach ($this->accounts as $account) {
            $id = DB::table('accounts')->where('code', $account['code'])->value('id');
            if ($id && ! DB::table('journal_lines')->where('account_id', $id)->exists()) {
                DB::table('accounts')->where('id', $id)->delete();
            }
        }
    }
};
