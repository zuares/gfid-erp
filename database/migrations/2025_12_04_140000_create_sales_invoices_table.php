<?php

// database/migrations/2025_12_04_140000_create_sales_invoices_table.php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('sales_invoices', function (Blueprint $table) {
            $table->id();

            $table->string('code', 50)->unique();
            $table->dateTime('date');

            $table->foreignId('customer_id')
                ->nullable()
                ->constrained('customers')
                ->nullOnDelete();

            $table->foreignId('marketplace_order_id')
                ->nullable()
                ->constrained('marketplace_orders')
                ->nullOnDelete();

            $table->foreignId('warehouse_id')
                ->constrained('warehouses');

            $table->enum('status', ['draft', 'posted', 'cancelled'])
                ->default('draft');

            // Totals
            $table->decimal('subtotal', 18, 2)->default(0); // total sebelum diskon header
            $table->decimal('discount_total', 18, 2)->default(0); // diskon header
            $table->decimal('tax_percent', 5, 2)->default(0); // contoh 0 / 11.00
            $table->decimal('tax_amount', 18, 2)->default(0);
            $table->decimal('grand_total', 18, 2)->default(0);

            $table->string('currency', 10)->default('IDR');

            $table->text('remarks')->nullable();

            $table->foreignId('created_by')
                ->nullable()
                ->constrained('users')
                ->nullOnDelete();

            $table->timestamps();

            $table->index('date');
            $table->index('status');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('sales_invoices');
    }
};
