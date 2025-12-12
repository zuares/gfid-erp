<?php

use App\Models\User;
use App\Models\Warehouse;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('inventory_adjustments', function (Blueprint $table) {
            $table->id();
            $table->string('code')->unique(); // ADJ-YYYYMMDD-XXX
            $table->date('date');

            $table->foreignIdFor(Warehouse::class)
                ->constrained()
                ->cascadeOnUpdate()
                ->restrictOnDelete();

            // Sumber adjustment (opsional, bisa dari SO, bisa manual)
            $table->string('source_type')->nullable(); // contoh: App\Models\StockOpname
            $table->unsignedBigInteger('source_id')->nullable();

            $table->string('reason')->nullable(); // ringkas
            $table->text('notes')->nullable(); // detail

            // ✅ align dengan controller + blade
            // draft = baru dibuat, belum diajukan (opsional)
            // pending = dibuat non-owner, menunggu owner approve (stok belum dikoreksi)
            // approved = stok sudah dikoreksi (final)
            // void = dibatalkan/di-void (tidak dipakai)
            $table->enum('status', ['draft', 'pending', 'approved', 'void'])
                ->default('draft');

            $table->foreignIdFor(User::class, 'created_by')
                ->constrained('users')
                ->cascadeOnUpdate()
                ->restrictOnDelete();

            $table->foreignIdFor(User::class, 'approved_by')
                ->nullable()
                ->constrained('users')
                ->nullOnDelete();

            $table->timestamp('approved_at')->nullable();

            $table->timestamps();

            // indexes
            $table->index(['date', 'warehouse_id'], 'inv_adj_date_wh_idx');
            $table->index(['status', 'date'], 'inv_adj_status_date_idx');
            $table->index(['source_type', 'source_id'], 'inv_adj_source_idx');
        });

        Schema::create('inventory_adjustment_lines', function (Blueprint $table) {
            $table->id();

            $table->foreignId('inventory_adjustment_id')
                ->constrained('inventory_adjustments')
                ->cascadeOnUpdate()
                ->cascadeOnDelete();

            $table->foreignId('item_id')
                ->constrained('items')
                ->cascadeOnUpdate()
                ->restrictOnDelete();

            // Opsional: kalau suatu hari perlu adjustment per LOT
            $table->foreignId('lot_id')
                ->nullable()
                ->constrained('lots')
                ->nullOnDelete();

            // Snapshot sebelum & sesudah adjustment (enak buat audit)
            $table->decimal('qty_before', 15, 3)->nullable();
            $table->decimal('qty_after', 15, 3)->nullable();

            // Perubahan qty (boleh plus / minus)
            $table->decimal('qty_change', 15, 3);

            // in / out hanya untuk referensi cepat,
            // sign di qty_change tetap dipakai di perhitungan.
            $table->enum('direction', ['in', 'out']);

            $table->text('notes')->nullable();

            $table->timestamps();

            $table->index(['inventory_adjustment_id', 'item_id'], 'inv_adj_lines_adj_item_idx');
            $table->index(['item_id', 'lot_id'], 'inv_adj_lines_item_lot_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('inventory_adjustment_lines');
        Schema::dropIfExists('inventory_adjustments');
    }
};
