<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('piecework_payroll_periods', function (Blueprint $table) {
            // finalize fields (kamu sudah pakai di controller, pastikan ada)
            if (!Schema::hasColumn('piecework_payroll_periods', 'finalized_at')) {
                $table->dateTime('finalized_at')->nullable();
            }
            if (!Schema::hasColumn('piecework_payroll_periods', 'finalized_by')) {
                $table->unsignedBigInteger('finalized_by')->nullable();
            }

            // accounting link
            if (!Schema::hasColumn('piecework_payroll_periods', 'journal_id')) {
                $table->unsignedBigInteger('journal_id')->nullable();
            }

            // payable account (default: Hutang Upah Borongan 2102)
            if (!Schema::hasColumn('piecework_payroll_periods', 'payable_account_id')) {
                $table->unsignedBigInteger('payable_account_id')->nullable();
            }

            // payment fields
            if (!Schema::hasColumn('piecework_payroll_periods', 'paid_at')) {
                $table->dateTime('paid_at')->nullable();
            }
            if (!Schema::hasColumn('piecework_payroll_periods', 'paid_by')) {
                $table->unsignedBigInteger('paid_by')->nullable();
            }
            if (!Schema::hasColumn('piecework_payroll_periods', 'paid_from_account_id')) {
                $table->unsignedBigInteger('paid_from_account_id')->nullable();
            }
        });
    }

    public function down(): void
    {
        Schema::table('piecework_payroll_periods', function (Blueprint $table) {
            foreach ([
                'finalized_at', 'finalized_by',
                'journal_id',
                'payable_account_id',
                'paid_at', 'paid_by', 'paid_from_account_id',
            ] as $col) {
                if (Schema::hasColumn('piecework_payroll_periods', $col)) {
                    $table->dropColumn($col);
                }
            }
        });
    }
};
