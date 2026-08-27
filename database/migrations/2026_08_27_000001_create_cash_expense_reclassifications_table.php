<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('cash_expense_reclassifications', function (Blueprint $table) {
            $table->id();
            $table->foreignId('cash_expense_id')
                ->constrained('cash_expenses')
                ->restrictOnDelete();
            $table->foreignId('from_expense_account_id')
                ->constrained('accounts')
                ->restrictOnDelete();
            $table->foreignId('to_expense_account_id')
                ->constrained('accounts')
                ->restrictOnDelete();
            $table->foreignId('journal_id')
                ->constrained('journals')
                ->restrictOnDelete();
            $table->decimal('amount', 18, 2);
            $table->string('reason', 255);
            $table->foreignId('created_by')
                ->nullable()
                ->constrained('users')
                ->nullOnDelete();
            $table->timestamps();

            $table->index(['cash_expense_id', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('cash_expense_reclassifications');
    }
};
