<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('cash_expenses', function (Blueprint $table) {
            $table->id();

            $table->date('date')->index();
            $table->decimal('amount', 18, 2);

            $table->foreignId('expense_account_id')
                ->constrained('accounts')
                ->restrictOnDelete();

            $table->foreignId('cash_account_id')
                ->constrained('accounts')
                ->restrictOnDelete();

            $table->string('description', 255)->nullable();
            $table->string('reference', 100)->nullable();

            // flow
            $table->string('status', 20)->default('draft')->index();

            // link journal saat posted
            $table->foreignId('journal_id')
                ->nullable()
                ->constrained('journals')
                ->nullOnDelete();

            $table->foreignId('created_by')
                ->nullable()
                ->constrained('users')
                ->nullOnDelete();

            $table->text('notes')->nullable();

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('cash_expenses');
    }
};
