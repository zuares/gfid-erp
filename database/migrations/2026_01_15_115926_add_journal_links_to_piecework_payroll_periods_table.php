<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('piecework_payroll_periods', function (Blueprint $table) {
            if (!Schema::hasColumn('piecework_payroll_periods', 'accrual_journal_id')) {
                $table->unsignedBigInteger('accrual_journal_id')->nullable();
            }

            if (!Schema::hasColumn('piecework_payroll_periods', 'payment_journal_id')) {
                $table->unsignedBigInteger('payment_journal_id')->nullable();
            }
        });
    }

    public function down(): void
    {
        Schema::table('piecework_payroll_periods', function (Blueprint $table) {
            if (Schema::hasColumn('piecework_payroll_periods', 'accrual_journal_id')) {
                $table->dropColumn('accrual_journal_id');
            }
            if (Schema::hasColumn('piecework_payroll_periods', 'payment_journal_id')) {
                $table->dropColumn('payment_journal_id');
            }
        });
    }
};
