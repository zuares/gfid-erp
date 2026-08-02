<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('shipment_lines', function (Blueprint $table) {
            $table->dropUnique(['shipment_id', 'item_id']);
            $table->foreignId('shipment_order_scan_id')
                ->nullable()
                ->after('shipment_id')
                ->constrained('shipment_order_scans')
                ->nullOnDelete();
            $table->index(['shipment_id', 'shipment_order_scan_id']);
            $table->unique(
                ['shipment_id', 'shipment_order_scan_id', 'item_id'],
                'shipment_lines_order_item_unique'
            );
        });
    }

    public function down(): void
    {
        Schema::table('shipment_lines', function (Blueprint $table) {
            $table->dropUnique('shipment_lines_order_item_unique');
            $table->dropIndex(['shipment_id', 'shipment_order_scan_id']);
            $table->dropForeign(['shipment_order_scan_id']);
            $table->dropColumn('shipment_order_scan_id');
            $table->unique(['shipment_id', 'item_id']);
        });
    }
};
