<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('shipment_order_scan_marketplace_orders', function (Blueprint $table) {
            $table->id();
            $table->foreignId('shipment_order_scan_id')
                ->constrained('shipment_order_scans')
                ->cascadeOnDelete();
            $table->foreignId('marketplace_order_id')
                ->constrained('marketplace_orders')
                ->cascadeOnDelete();
            $table->foreignId('fulfillment_id')
                ->nullable()
                ->constrained('order_fulfillments')
                ->nullOnDelete();
            $table->unique(
                ['shipment_order_scan_id', 'marketplace_order_id'],
                'shipment_scan_marketplace_order_unique'
            );
            $table->index(['shipment_order_scan_id', 'fulfillment_id'], 'shipment_scan_fulfillment_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('shipment_order_scan_marketplace_orders');
    }
};
