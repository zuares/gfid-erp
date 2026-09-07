<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('supplier_ap_opening_balances', function (Blueprint $table) {
            $table->id();
            $table->foreignId('supplier_id')->constrained('suppliers')->restrictOnDelete();
            $table->foreignId('journal_id')->nullable()->unique()->constrained('journals')->nullOnDelete();
            $table->date('date')->index();
            $table->date('invoice_date')->nullable();
            $table->date('due_date')->nullable();
            $table->string('reference_no', 100)->nullable();
            $table->decimal('amount', 18, 2);
            $table->foreignId('ap_account_id')->constrained('accounts')->restrictOnDelete();
            $table->foreignId('offset_account_id')->constrained('accounts')->restrictOnDelete();
            $table->text('notes')->nullable();
            $table->string('status', 20)->default('posted')->index();
            $table->timestamp('posted_at')->nullable();
            $table->foreignId('posted_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('voided_at')->nullable();
            $table->foreignId('voided_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->index(['supplier_id', 'date']);
            $table->index(['supplier_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('supplier_ap_opening_balances');
    }
};
