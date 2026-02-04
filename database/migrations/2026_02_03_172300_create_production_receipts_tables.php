<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('production_receipts', function (Blueprint $table) {
            $table->id();
            $table->string('code')->unique();
            $table->date('date');
            $table->foreignId('production_order_id')->constrained('production_orders')->cascadeOnDelete();

            $table->foreignId('from_warehouse_id')->constrained('warehouses');
            $table->foreignId('to_warehouse_id')->constrained('warehouses');

            $table->string('status')->default('draft'); // draft|posted|void
            $table->dateTime('posted_at')->nullable();
            $table->unsignedBigInteger('posted_by')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->index(['production_order_id', 'date']);
            $table->index(['status']);
        });

        Schema::create('production_receipt_lines', function (Blueprint $table) {
            $table->id();
            $table->foreignId('production_receipt_id')->constrained('production_receipts')->cascadeOnDelete();
            $table->foreignId('item_id')->constrained('items'); // FG item
            $table->decimal('qty_good', 14, 2);

            $table->unsignedBigInteger('lot_id')->nullable();
            $table->decimal('unit_cost', 14, 4)->nullable(); // optional

            $table->timestamps();

            $table->index(['production_receipt_id', 'item_id']);
            $table->index(['lot_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('production_receipt_lines');
        Schema::dropIfExists('production_receipts');
    }
};
