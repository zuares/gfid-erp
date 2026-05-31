<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Pisah tracking WIP cutting dari posisi hilir.
     *
     * - cut_wip_warehouse_id : lokasi WIP hasil cutting (normalnya WIP-CUT).
     *   DI-SET SEKALI saat QC Cutting. Tahap jahit/finishing DILARANG menulis kolom ini,
     *   sehingga sisa cutting tidak pernah "hilang" dari halaman Ambil Jahit.
     * - cut_wip_qty : qty gross hasil cutting OK yang diposting ke cut WIP (= qty_qc_ok).
     *
     * Kolom lama wip_warehouse_id / wip_qty tetap dipakai untuk posisi hilir
     * (retur jahit / finishing) — sengaja TIDAK diubah agar blast radius kecil.
     */
    public function up(): void
    {
        Schema::table('cutting_job_bundles', function (Blueprint $table) {
            $table->unsignedBigInteger('cut_wip_warehouse_id')->nullable()->after('wip_qty');
            $table->decimal('cut_wip_qty', 15, 3)->default(0)->after('cut_wip_warehouse_id');

            $table->index('cut_wip_warehouse_id');
        });
    }

    public function down(): void
    {
        Schema::table('cutting_job_bundles', function (Blueprint $table) {
            $table->dropIndex(['cut_wip_warehouse_id']);
            $table->dropColumn(['cut_wip_warehouse_id', 'cut_wip_qty']);
        });
    }
};
