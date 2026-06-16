<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * PR-D: Additive — tambah kolom purchase_request_id ke purchase_orders.
 * Nullable FK, nullOnDelete — PO existing tidak terpengaruh.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasColumn('purchase_orders', 'purchase_request_id')) {
            return; // idempoten
        }

        Schema::table('purchase_orders', function (Blueprint $table) {
            $table->unsignedBigInteger('purchase_request_id')
                ->nullable()
                ->after('notes');

            $table->foreign('purchase_request_id')
                ->references('id')
                ->on('purchase_requests')
                ->nullOnDelete();

            $table->index('purchase_request_id');
        });
    }

    public function down(): void
    {
        Schema::table('purchase_orders', function (Blueprint $table) {
            if (Schema::hasColumn('purchase_orders', 'purchase_request_id')) {
                $table->dropForeign(['purchase_request_id']);
                $table->dropIndex(['purchase_request_id']);
                $table->dropColumn('purchase_request_id');
            }
        });
    }
};
