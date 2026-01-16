<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('purchase_payments', function (Blueprint $table) {
            $table->id();

            $table->foreignId('purchase_order_id')
                ->constrained('purchase_orders')
                ->cascadeOnDelete();

            $table->date('date');

            $table->foreignId('payment_method_id')
                ->constrained('payment_methods');

            // ✅ sumber uang keluar: akun kas/bank di COA (1101 / 111x)
            $table->foreignId('cash_account_id')
                ->nullable()
                ->constrained('accounts');

            // dp = uang muka, payment = pelunasan (atau pembayaran biasa)
            $table->string('type', 20)->default('payment'); // dp|payment

            $table->decimal('amount', 18, 2)->default(0);

            $table->string('ref_no', 100)->nullable();
            $table->string('notes', 255)->nullable();

            $table->foreignId('created_by')->nullable()->constrained('users');

            // VOID
            $table->timestamp('voided_at')->nullable();
            $table->foreignId('voided_by')->nullable()->constrained('users');

            $table->timestamps();

            $table->index(['purchase_order_id', 'date']);
            $table->index(['type']);
            $table->index(['voided_at']);
            $table->index(['cash_account_id']);
            // $table->index('payment_status');
            // $table->index('due_date');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('purchase_payments');
    }
};
