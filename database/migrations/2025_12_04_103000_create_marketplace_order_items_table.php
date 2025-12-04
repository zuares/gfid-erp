<?php

// database/migrations/2025_12_04_103000_create_marketplace_order_items_table.php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('marketplace_order_items', function (Blueprint $table) {
            $table->id();

            $table->foreignId('order_id')
                ->constrained('marketplace_orders')
                ->cascadeOnDelete();

            $table->unsignedInteger('line_no')->default(1);

            $table->string('external_item_id', 100)->nullable();
            $table->string('external_sku', 100)->nullable();

            $table->foreignId('item_id')->nullable()
                ->constrained('items')
                ->nullOnDelete();

            // snapshot
            $table->string('item_code_snapshot', 100)->nullable();
            $table->string('item_name_snapshot', 190)->nullable();
            $table->string('variant_snapshot', 190)->nullable();

            $table->integer('qty')->default(0);

            $table->decimal('price_original', 15, 2)->default(0);
            $table->decimal('price_after_discount', 15, 2)->default(0);
            $table->decimal('line_discount', 15, 2)->default(0);
            $table->decimal('line_gross_amount', 15, 2)->default(0);
            $table->decimal('line_net_amount', 15, 2)->default(0);

            // nanti diisi saat link ke Shipment / Invoice
            $table->decimal('hpp_unit_snapshot', 15, 4)->default(0);
            $table->decimal('hpp_total_snapshot', 15, 4)->default(0);

            $table->timestamps();

            $table->index(['order_id', 'line_no']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('marketplace_order_items');
    }
};
