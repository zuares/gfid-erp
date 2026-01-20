<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('payment_methods', function (Blueprint $table) {
            if (!Schema::hasColumn('payment_methods', 'mode')) {
                $table->string('mode', 20)->nullable()->after('name'); // cash|transfer|credit
            }
            if (!Schema::hasColumn('payment_methods', 'default_cash_account_id')) {
                $table->foreignId('default_cash_account_id')
                    ->nullable()
                    ->constrained('accounts')
                    ->nullOnDelete();
            }
        });
    }

    public function down(): void
    {
        Schema::table('payment_methods', function (Blueprint $table) {
            if (Schema::hasColumn('payment_methods', 'default_cash_account_id')) {
                $table->dropConstrainedForeignId('default_cash_account_id');
            }
            if (Schema::hasColumn('payment_methods', 'mode')) {
                $table->dropColumn('mode');
            }
        });
    }
};
