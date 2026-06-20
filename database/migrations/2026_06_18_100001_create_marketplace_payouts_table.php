<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('marketplace_payouts', function (Blueprint $table) {
            $table->id();
            $table->date('date');
            $table->string('marketplace_name');           // Shopee, Tokopedia, TikTok, dll
            $table->decimal('amount', 15, 2);
            $table->foreignId('bank_account_id')
                  ->constrained('accounts')
                  ->restrictOnDelete();
            $table->string('reference')->nullable();     // nomor disbursement/settlement
            $table->string('description')->nullable();
            $table->string('status')->default('draft');  // draft | posted | void
            $table->foreignId('journal_id')->nullable()->constrained('journals')->nullOnDelete();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->text('notes')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('marketplace_payouts');
    }
};
