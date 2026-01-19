<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('journal_lines', function (Blueprint $table) {
            $table->id();

            $table->foreignId('journal_id')
                ->constrained('journals')
                ->cascadeOnDelete();

            // akun COA
            $table->foreignId('account_id')
                ->constrained('accounts')
                ->restrictOnDelete();

            // salah satu harus 0
            $table->decimal('debit', 18, 2)->default(0);
            $table->decimal('credit', 18, 2)->default(0);

            $table->timestamps();

            $table->index(['journal_id', 'account_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('journal_lines');
    }
};
