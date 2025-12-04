<?php

// database/migrations/2025_12_04_140100_create_sales_invoice_lines_table.php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('sales_invoice_lines', function (Blueprint $table) {
            $table->id();

            $table->foreignId('sales_invoice_id')
                ->constrained('sales_invoices')
                ->cascadeOnDelete();

            $table->integer('line_no')->default(1);

            $table->foreignId('item_id')
                ->constrained('items');

            // Snapshot untuk jaga-jaga kalau nama/kode item berubah
            $table->string('item_code_snapshot', 50)->nullable();
            $table->string('item_name_snapshot', 190)->nullable();

            // Qty & price
            $table->decimal('qty', 18, 4)->default(0);
            $table->decimal('unit_price', 18, 2)->default(0);

            // Diskon per line (nominal)
            $table->decimal('line_discount', 18, 2)->default(0);

            // Total line setelah diskon
            $table->decimal('line_total', 18, 2)->default(0);

            // HPP snapshot
            $table->decimal('hpp_unit_snapshot', 18, 4)->default(0);
            $table->decimal('hpp_total_snapshot', 18, 2)->default(0);

            // Warehouse & LOT (optional)
            $table->foreignId('warehouse_id')
                ->constrained('warehouses');

            $table->foreignId('lot_id')
                ->nullable()
                ->constrained('lots')
                ->nullOnDelete();

            $table->timestamps();

            $table->index(['sales_invoice_id', 'line_no']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('sales_invoice_lines');
    }
};
