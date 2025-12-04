<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('item_cost_snapshots', function (Blueprint $table) {
            $table->id();

            // Item & gudang terkait (biasanya gudang FG / RTS)
            $table->unsignedBigInteger('item_id');
            $table->unsignedBigInteger('warehouse_id')->nullable();

            // Tanggal snapshot / effective date
            $table->date('snapshot_date');

            // Optional referensi (periode, job, dokumen)
            $table->string('reference_type')->nullable(); // e.g. 'cutting_job', 'sewing_period', 'manual'
            $table->unsignedBigInteger('reference_id')->nullable();

            // Basis qty (misal total qty yang dihitung HPP-nya) – opsional
            $table->decimal('qty_basis', 15, 3)->nullable();

            // Komponen HPP per unit (per pcs / meter / dsb)
            $table->decimal('rm_unit_cost', 15, 4)->default(0); // kain / raw material
            $table->decimal('cutting_unit_cost', 15, 4)->default(0); // biaya cutting per unit
            $table->decimal('sewing_unit_cost', 15, 4)->default(0); // biaya sewing per unit
            $table->decimal('finishing_unit_cost', 15, 4)->default(0); // finishing (press, trimming, dsb)
            $table->decimal('packaging_unit_cost', 15, 4)->default(0); // polybag, hanger, label, dsb
            $table->decimal('overhead_unit_cost', 15, 4)->default(0); // overhead lain (optional)

            // Hasil total HPP per unit (RM + cutting + sewing + finishing + packaging + overhead)
            $table->decimal('total_unit_cost', 15, 4);

            // Catatan tambahan
            $table->text('notes')->nullable();

            // Audit
            $table->unsignedBigInteger('created_by')->nullable();
            $table->timestamps();

            // Index & FK
            $table->foreign('item_id')->references('id')->on('items')->cascadeOnDelete();
            $table->foreign('warehouse_id')->references('id')->on('warehouses')->nullOnDelete();
            $table->index(['snapshot_date', 'item_id']);
            $table->index(['reference_type', 'reference_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('item_cost_snapshots');
    }
};
