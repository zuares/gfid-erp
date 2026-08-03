<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasColumn('shipment_return_lines', 'scanned_at')) {
            Schema::table('shipment_return_lines', function (Blueprint $table) {
                $table->timestamp('scanned_at')->nullable()->after('qty');
                $table->index(['shipment_return_id', 'scanned_at'], 'sr_lines_return_scanned_at_idx');
            });
        }

        if (!Schema::hasColumn('shipment_return_order_scans', 'scanned_at')) {
            Schema::table('shipment_return_order_scans', function (Blueprint $table) {
                $table->timestamp('scanned_at')->nullable()->after('order_number');
                $table->index(['shipment_return_id', 'scanned_at'], 'sr_order_scans_return_scanned_at_idx');
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasColumn('shipment_return_order_scans', 'scanned_at')) {
            Schema::table('shipment_return_order_scans', function (Blueprint $table) {
                $table->dropIndex('sr_order_scans_return_scanned_at_idx');
                $table->dropColumn('scanned_at');
            });
        }

        if (Schema::hasColumn('shipment_return_lines', 'scanned_at')) {
            Schema::table('shipment_return_lines', function (Blueprint $table) {
                $table->dropIndex('sr_lines_return_scanned_at_idx');
                $table->dropColumn('scanned_at');
            });
        }
    }
};
