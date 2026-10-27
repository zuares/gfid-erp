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
            $table->dropIndex('standalone_accounting_accounts_cash_category_index');
            $table->dropIndex('standalone_accounting_accounts_source_code_index');
        });

        Schema::table('standalone_accounting_accounts', function (Blueprint $table) {
            $table->dropColumn(['cash_category', 'source_code']);
        });
    }

    public function down(): void
    {
        Schema::table('standalone_accounting_accounts', function (Blueprint $table) {
            $table->string('cash_category', 20)->nullable()->index();
            $table->string('source_code', 30)->nullable()->index();
        });

        DB::table('standalone_accounting_accounts')->where('code', '1000')->update(['cash_category' => 'cash']);
        DB::table('standalone_accounting_accounts')->where('code', '1100')->update(['cash_category' => 'bank']);
    }
};
