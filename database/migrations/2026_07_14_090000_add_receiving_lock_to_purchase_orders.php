<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Menambah mekanisme "receiving lock" pada purchase_orders.
 *
 * Tujuan: begitu GRN pertama merujuk ke sebuah PO (termasuk PO draft),
 * PO dikunci agar line/supplier/nomor tidak bisa diubah sehingga
 * referensi purchase_order_line_id pada GRN tetap valid.
 *
 * Semua kolom nullable & aditif — tidak merusak data lama (PO approved / GRN posted
 * yang sudah ada tetap bekerja; locked_at NULL = belum dikunci).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('purchase_orders', function (Blueprint $table) {
            if (!Schema::hasColumn('purchase_orders', 'receiving_started_at')) {
                $table->timestamp('receiving_started_at')->nullable()->after('received_status');
            }
            if (!Schema::hasColumn('purchase_orders', 'locked_at')) {
                $table->timestamp('locked_at')->nullable()->after('receiving_started_at');
            }
            if (!Schema::hasColumn('purchase_orders', 'locked_by')) {
                $table->unsignedBigInteger('locked_by')->nullable()->after('locked_at');
            }
            if (!Schema::hasColumn('purchase_orders', 'lock_reason')) {
                $table->string('lock_reason', 255)->nullable()->after('locked_by');
            }
            if (!Schema::hasColumn('purchase_orders', 'first_grn_id')) {
                $table->unsignedBigInteger('first_grn_id')->nullable()->after('lock_reason');
            }
        });

        // Index terpisah + guard agar aman diulang.
        Schema::table('purchase_orders', function (Blueprint $table) {
            try {
                $table->index('locked_at', 'po_locked_at_index');
            } catch (\Throwable $e) {
                // index sudah ada — abaikan
            }
        });
    }

    public function down(): void
    {
        Schema::table('purchase_orders', function (Blueprint $table) {
            try {
                $table->dropIndex('po_locked_at_index');
            } catch (\Throwable $e) {
                // abaikan
            }
        });

        Schema::table('purchase_orders', function (Blueprint $table) {
            foreach (['first_grn_id', 'lock_reason', 'locked_by', 'locked_at', 'receiving_started_at'] as $col) {
                if (Schema::hasColumn('purchase_orders', $col)) {
                    $table->dropColumn($col);
                }
            }
        });
    }
};
