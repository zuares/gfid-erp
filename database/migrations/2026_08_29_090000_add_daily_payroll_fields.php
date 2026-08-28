<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('employees', function (Blueprint $table) {
            $table->decimal('daily_rate', 15, 2)
                ->default(0)
                ->after('weekly_fixed_salary');
        });

        Schema::table('piecework_payroll_lines', function (Blueprint $table) {
            $table->date('work_date')
                ->nullable()
                ->after('employee_id');
            $table->string('attendance_status', 20)
                ->nullable()
                ->after('work_date');
            $table->decimal('attendance_factor', 5, 2)
                ->default(0)
                ->after('attendance_status');
            $table->decimal('rate_per_day', 12, 2)
                ->default(0)
                ->after('rate_per_pcs');

            $table->index(['payroll_period_id', 'work_date'], 'ppl_period_work_date_idx');
        });
    }

    public function down(): void
    {
        Schema::table('piecework_payroll_lines', function (Blueprint $table) {
            $table->dropIndex('ppl_period_work_date_idx');
            $table->dropColumn([
                'work_date',
                'attendance_status',
                'attendance_factor',
                'rate_per_day',
            ]);
        });

        Schema::table('employees', function (Blueprint $table) {
            $table->dropColumn('daily_rate');
        });
    }
};
