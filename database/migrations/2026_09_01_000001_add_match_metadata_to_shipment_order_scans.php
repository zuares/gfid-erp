<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('shipment_order_scans', function (Blueprint $table) {
            if (!Schema::hasColumn('shipment_order_scans', 'match_method')) {
                $table->string('match_method', 30)->nullable()->after('source');
            }

            if (!Schema::hasColumn('shipment_order_scans', 'match_reason')) {
                $table->string('match_reason', 120)->nullable()->after('match_method');
            }

            if (!Schema::hasColumn('shipment_order_scans', 'matched_at')) {
                $table->timestamp('matched_at')->nullable()->after('match_reason');
            }

            $table->index(['shipment_id', 'fulfillment_id'], 'shipment_scans_fulfillment_idx');
            $table->index(['shipment_id', 'match_method'], 'shipment_scans_match_method_idx');
        });
    }

    public function down(): void
    {
        Schema::table('shipment_order_scans', function (Blueprint $table) {
            $table->dropIndex('shipment_scans_fulfillment_idx');
            $table->dropIndex('shipment_scans_match_method_idx');

            foreach (['match_method', 'match_reason', 'matched_at'] as $column) {
                if (Schema::hasColumn('shipment_order_scans', $column)) {
                    $table->dropColumn($column);
                }
            }
        });
    }
};
