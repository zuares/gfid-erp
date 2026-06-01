<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * production_movements — JURNAL produksi (anotasi kaya di atas ledger stok).
 *
 * Bukan ledger stok. Sumber kebenaran stok TETAP inventory_mutations
 * (di-maintain InventoryService). Tabel ini mencatat konteks produksi yang
 * tidak ada di inventory_mutations: kode, batch, penjahit, deadline, dari/ke
 * status, dan user pembuat — sambil menautkan ke baris mutasi yang dihasilkan.
 *
 * Aditif sepenuhnya: rollback tabel ini tidak mempengaruhi sistem lama.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('production_movements', function (Blueprint $table) {
            $table->id();
            $table->string('code')->unique();              // MUT-YYYYMMDD-NNN
            $table->date('date');

            $table->foreignId('cutting_job_bundle_id')->nullable()   // = batch produksi
                ->constrained('cutting_job_bundles')->nullOnDelete();
            $table->foreignId('item_id')->constrained('items');       // SKU / varian

            $table->foreignId('from_warehouse_id')->nullable()->constrained('warehouses');
            $table->foreignId('to_warehouse_id')->nullable()->constrained('warehouses');
            $table->string('from_status')->nullable();     // slug status (rencana, siap-jahit, ...)
            $table->string('to_status')->nullable();

            $table->decimal('qty', 14, 3)->default(0);

            $table->foreignId('operator_id')->nullable()   // penjahit
                ->constrained('employees')->nullOnDelete();
            $table->date('deadline')->nullable();
            $table->text('notes')->nullable();

            $table->foreignId('created_by')->nullable()    // user pembuat
                ->constrained('users')->nullOnDelete();
            $table->foreignId('inventory_mutation_id')->nullable()  // jejak ke mutasi stok IN
                ->constrained('inventory_mutations')->nullOnDelete();

            $table->timestamps();

            $table->index('date', 'idx_pm_date');
            $table->index('item_id', 'idx_pm_item');
            $table->index(['to_warehouse_id', 'date'], 'idx_pm_to_date');
            $table->index('cutting_job_bundle_id', 'idx_pm_bundle');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('production_movements');
    }
};
