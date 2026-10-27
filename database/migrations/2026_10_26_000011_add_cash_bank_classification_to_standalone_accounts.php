<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('standalone_accounting_accounts', function (Blueprint $table) {
            $table->string('cash_category', 20)->nullable()->index();
            $table->string('source_code', 30)->nullable()->index();
        });

        DB::table('standalone_accounting_accounts')
            ->where('code', '1000')
            ->update(['cash_category' => 'cash']);

        DB::table('standalone_accounting_accounts')
            ->where('code', '1100')
            ->update(['cash_category' => 'bank']);

        $now = now();

        foreach ([
            ['code' => '1101', 'name' => 'Kas Tunai', 'cash_category' => 'cash'],
            ['code' => '1111', 'name' => 'Bank Jago (Bisnis)', 'cash_category' => 'bank'],
            ['code' => '1112', 'name' => 'Bank BCA (Operasional)', 'cash_category' => 'bank'],
            ['code' => '1113', 'name' => 'Seabank', 'cash_category' => 'bank'],
            ['code' => '1114', 'name' => 'Aladin Bank', 'cash_category' => 'bank'],
        ] as $account) {
            DB::table('standalone_accounting_accounts')->updateOrInsert(
                ['code' => $account['code']],
                [
                    'name' => $account['name'],
                    'type' => 'asset',
                    'cash_category' => $account['cash_category'],
                    'source_code' => $account['code'],
                    'is_active' => true,
                    'created_at' => $now,
                    'updated_at' => $now,
                ],
            );
        }
    }

    public function down(): void
    {
        Schema::table('standalone_accounting_accounts', function (Blueprint $table) {
            $table->dropColumn(['cash_category', 'source_code']);
        });
    }
};
