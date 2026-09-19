<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('employee_savings_transactions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('employee_id')->constrained('employees')->restrictOnDelete();
            $table->foreignId('payroll_period_id')->nullable()->constrained('piecework_payroll_periods')->nullOnDelete();
            $table->date('date');
            $table->decimal('amount', 15, 2);
            $table->string('type', 30)->default('deposit');
            $table->string('status', 30)->default('posted');
            $table->foreignId('journal_id')->nullable()->constrained('journals')->nullOnDelete();
            $table->string('source_type', 60)->nullable();
            $table->unsignedBigInteger('source_id')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->index(['employee_id', 'date']);
            $table->index(['source_type', 'source_id']);
        });

        if (Schema::hasTable('accounts')) {
            DB::table('accounts')->updateOrInsert(
                ['code' => '2104'],
                [
                    'name' => 'Tabungan Karyawan',
                    'type' => 'liability',
                    'is_cash' => false,
                    'is_active' => true,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]
            );
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('employee_savings_transactions');
        DB::table('accounts')->where('code', '2104')->delete();
    }
};
