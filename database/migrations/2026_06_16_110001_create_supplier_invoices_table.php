<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('supplier_invoices', function (Blueprint $table) {
            $table->id();

            // Nomor invoice dari sistem (auto-generate)
            $table->string('invoice_no', 64)->unique();

            // Nomor invoice dari supplier (opsional, berbeda dari invoice_no)
            $table->string('supplier_invoice_ref', 100)->nullable()
                ->comment('Nomor invoice asli dari supplier');

            $table->foreignId('supplier_id')->constrained('suppliers')->cascadeOnUpdate();
            $table->foreignId('purchase_order_id')->nullable()->constrained('purchase_orders')->nullOnDelete();

            $table->date('invoice_date');
            $table->date('due_date')->nullable();

            // Nilai
            $table->decimal('subtotal', 18, 2)->default(0)
                ->comment('Total dari GRN posted');
            $table->decimal('discount_amount', 18, 2)->default(0);
            $table->decimal('return_deduction_amount', 18, 2)->default(0)
                ->comment('Potongan dari retur barang');
            $table->decimal('total_amount', 18, 2)->default(0)
                ->comment('subtotal - discount - return_deduction');
            $table->decimal('paid_amount', 18, 2)->default(0);

            // Status: draft | posted | partial_paid | paid | void
            $table->string('status', 20)->default('draft');

            $table->text('notes')->nullable();

            // Lifecycle timestamps
            $table->timestamp('posted_at')->nullable();
            $table->foreignId('posted_by')->nullable()->constrained('users')->nullOnDelete();

            $table->timestamp('voided_at')->nullable();
            $table->foreignId('voided_by')->nullable()->constrained('users')->nullOnDelete();

            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();

            $table->timestamps();

            $table->index(['supplier_id', 'invoice_date']);
            $table->index(['purchase_order_id']);
            $table->index(['status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('supplier_invoices');
    }
};
