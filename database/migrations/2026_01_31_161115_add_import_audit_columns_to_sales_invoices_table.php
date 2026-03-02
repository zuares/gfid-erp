<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('sales_invoices', function (Blueprint $table) {
            // Audit import
            if (!Schema::hasColumn('sales_invoices', 'import_batch_id')) {
                $table->string('import_batch_id', 40)->nullable()->after('remarks');
            }
            if (!Schema::hasColumn('sales_invoices', 'raw_source_file')) {
                $table->string('raw_source_file', 255)->nullable()->after('import_batch_id');
            }

            // Index untuk filter/report cepat
            $table->index(['store_id', 'channel', 'paid_at'], 'si_store_channel_paid_idx');
            $table->index(['store_id', 'marketplace_status'], 'si_store_mstatus_idx');
            $table->index(['store_id', 'awb'], 'si_store_awb_idx');
            $table->index(['import_batch_id'], 'si_import_batch_idx');

            /**
             * Optional tapi sangat berguna:
             * Unique anti-duplikat import per store+channel+order_no
             *
             * ⚠️ CATATAN PRODUKSI:
             * Kalau sudah ada data duplikat, migration akan gagal.
             * Pastikan dulu tidak ada duplikat:
             * SELECT store_id, channel, channel_order_no, COUNT(*)
             * FROM sales_invoices
             * WHERE channel_order_no IS NOT NULL AND channel_order_no <> ''
             * GROUP BY store_id, channel, channel_order_no
             * HAVING COUNT(*) > 1;
             */
            $table->unique(['store_id', 'channel', 'channel_order_no'], 'si_store_channel_orderno_uniq');
        });
    }

    public function down(): void
    {
        Schema::table('sales_invoices', function (Blueprint $table) {
            // drop unique & indexes
            $table->dropUnique('si_store_channel_orderno_uniq');
            $table->dropIndex('si_store_channel_paid_idx');
            $table->dropIndex('si_store_mstatus_idx');
            $table->dropIndex('si_store_awb_idx');
            $table->dropIndex('si_import_batch_idx');

            // drop columns
            if (Schema::hasColumn('sales_invoices', 'raw_source_file')) {
                $table->dropColumn('raw_source_file');
            }
            if (Schema::hasColumn('sales_invoices', 'import_batch_id')) {
                $table->dropColumn('import_batch_id');
            }
        });
    }
};
