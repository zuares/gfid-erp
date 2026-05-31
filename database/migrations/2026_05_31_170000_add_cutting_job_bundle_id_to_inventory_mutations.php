<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * FASE 0 — Rombak ke "ledger bertag".
 *
 * Tambah dimensi produksi ke ledger: cutting_job_bundle_id.
 *
 * Tujuan akhir (fase berikutnya): readiness (Ambil Jahit / Siap Finishing)
 * dihitung LANGSUNG dari inventory_mutations difilter per bundle, sehingga
 * tidak ada lagi kolom cache kuantitas yang bisa drift (stok hantu mustahil).
 *
 * Kolom ini NULLABLE & TANPA foreign-key keras (sama seperti source_id) supaya:
 *   - aman untuk data lama yang belum bisa diturunkan ke bundle,
 *   - tidak memblok movement non-produksi (purchase, shipment, opname, dll).
 *
 * Aditif murni — tidak mengubah/menghapus kolom apa pun. Nol risiko ke costing.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('inventory_mutations', function (Blueprint $table) {
            if (!Schema::hasColumn('inventory_mutations', 'cutting_job_bundle_id')) {
                $table->unsignedBigInteger('cutting_job_bundle_id')
                    ->nullable()
                    ->after('source_id');

                $table->index('cutting_job_bundle_id', 'inv_mut_cjb_idx');
            }
        });
    }

    public function down(): void
    {
        Schema::table('inventory_mutations', function (Blueprint $table) {
            if (Schema::hasColumn('inventory_mutations', 'cutting_job_bundle_id')) {
                $table->dropIndex('inv_mut_cjb_idx');
                $table->dropColumn('cutting_job_bundle_id');
            }
        });
    }
};
