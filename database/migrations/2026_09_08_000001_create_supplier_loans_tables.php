<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('supplier_loans', function (Blueprint $table) {
            $table->id();
            $table->foreignId('supplier_id')->constrained()->restrictOnDelete();
            $table->date('date')->index();
            $table->date('due_date')->nullable()->index();
            $table->decimal('principal_amount', 18, 2);
            $table->foreignId('cash_account_id')->constrained('accounts')->restrictOnDelete();
            $table->foreignId('receivable_account_id')->constrained('accounts')->restrictOnDelete();
            $table->string('description', 255)->nullable();
            $table->string('reference', 100)->nullable();
            $table->string('status', 20)->default('draft')->index();
            $table->foreignId('journal_id')->nullable()->constrained('journals')->nullOnDelete();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->index(['supplier_id', 'status']);
        });

        Schema::create('supplier_loan_repayments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('supplier_loan_id')->constrained('supplier_loans')->cascadeOnDelete();
            $table->date('date')->index();
            $table->decimal('amount', 18, 2);
            $table->foreignId('cash_account_id')->constrained('accounts')->restrictOnDelete();
            $table->string('reference', 100)->nullable();
            $table->string('status', 20)->default('draft')->index();
            $table->foreignId('journal_id')->nullable()->constrained('journals')->nullOnDelete();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->index(['supplier_loan_id', 'date']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('supplier_loan_repayments');
        Schema::dropIfExists('supplier_loans');
    }
};
