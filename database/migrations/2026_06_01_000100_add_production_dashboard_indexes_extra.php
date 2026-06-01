<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Index performa tambahan untuk Dashboard Produksi (tahap finishing & stok).
 *
 * - finishing_job_lines(finishing_job_id): join induk per finishing job, belum ada index.
 * - finishing_job_lines(operator_id): filter performa operator finishing. Catatan:
 *   sewing_operator_id sudah ber-index, tapi operator_id (operator finishing) belum.
 * - inventory_mutations(warehouse_id, date): alert days-of-cover memfilter stok per
 *   gudang + tanggal. Index existing dipimpin item_id (item_id, warehouse_id, date)
 *   sehingga tidak terpakai untuk filter warehouse_id+date tanpa item_id (leftmost prefix).
 *
 * Murni read-side / performa — tidak mengubah data atau perilaku tulis.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('finishing_job_lines', function (Blueprint $table) {
            $table->index('finishing_job_id', 'idx_fjl_job');
            $table->index('operator_id', 'idx_fjl_operator');
        });

        Schema::table('inventory_mutations', function (Blueprint $table) {
            $table->index(['warehouse_id', 'date'], 'idx_im_wh_date');
        });
    }

    public function down(): void
    {
        Schema::table('finishing_job_lines', function (Blueprint $table) {
            $table->dropIndex('idx_fjl_job');
            $table->dropIndex('idx_fjl_operator');
        });

        Schema::table('inventory_mutations', function (Blueprint $table) {
            $table->dropIndex('idx_im_wh_date');
        });
    }
};
