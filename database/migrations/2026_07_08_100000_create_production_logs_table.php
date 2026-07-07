<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * production_logs — audit trail APPEND-ONLY untuk event produksi yang TIDAK
 * meninggalkan jejak di ledger stok/jurnal.
 *
 * Contoh: "Bersihkan Data Produksi", QC dibatalkan, perubahan status,
 * approval besar. Event yang berdampak stok/nilai sudah tercatat di
 * inventory_mutations & journals — jangan diduplikasi ke sini.
 *
 * Halaman "Log Produksi" (timeline) nanti menggabungkan tabel ini dengan
 * inventory_mutations + journals + production_movements.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('production_logs', function (Blueprint $table) {
            $table->id();
            $table->string('event')->index();                 // clean_production, qc_cancelled, ...
            $table->foreignId('actor_id')->nullable()         // siapa yang melakukan
                ->constrained('users')->nullOnDelete();
            $table->string('source_type')->nullable();        // dokumen terkait (opsional)
            $table->unsignedBigInteger('source_id')->nullable();
            $table->string('reference')->nullable();          // kode / reference_no (opsional)
            $table->text('summary')->nullable();              // ringkasan manusiawi
            $table->json('meta')->nullable();                 // detail terstruktur (counts, backup, dsb.)
            $table->timestamps();

            $table->index(['source_type', 'source_id']);
            $table->index('created_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('production_logs');
    }
};
