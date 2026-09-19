<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('employee_loans', function (Blueprint $table) {
            $table->id();
            $table->foreignId('employee_id')->constrained('employees')->restrictOnDelete();
            $table->date('date')->index();
            $table->date('due_date')->nullable()->index();
            $table->decimal('principal_amount', 18, 2);
            $table->decimal('installment_amount', 18, 2)->nullable();
            $table->unsignedInteger('installment_count')->nullable();
            $table->foreignId('cash_account_id')->constrained('accounts')->restrictOnDelete();
            $table->foreignId('receivable_account_id')->constrained('accounts')->restrictOnDelete();
            $table->string('description', 255)->nullable();
            $table->string('reference', 100)->nullable();
            $table->string('status', 20)->default('draft')->index();
            $table->foreignId('journal_id')->nullable()->constrained('journals')->nullOnDelete();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->index(['employee_id', 'status']);
        });

        Schema::create('employee_loan_repayments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('employee_loan_id')->constrained('employee_loans')->cascadeOnDelete();
            $table->date('date')->index();
            $table->decimal('amount', 18, 2);
            $table->foreignId('cash_account_id')->constrained('accounts')->restrictOnDelete();
            $table->string('reference', 100)->nullable();
            $table->string('status', 20)->default('draft')->index();
            $table->foreignId('journal_id')->nullable()->constrained('journals')->nullOnDelete();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->index(['employee_loan_id', 'date']);
        });

        $now = now();
        DB::table('accounts')->updateOrInsert(
            ['code' => '1307'],
            [
                'name' => 'Piutang Pinjaman Karyawan',
                'type' => 'asset',
                'is_cash' => false,
                'is_active' => true,
                'updated_at' => $now,
                'created_at' => $now,
            ],
        );
    }

    public function down(): void
    {
        Schema::dropIfExists('employee_loan_repayments');
        Schema::dropIfExists('employee_loans');
        DB::table('accounts')->where('code', '1307')->delete();
    }
};
