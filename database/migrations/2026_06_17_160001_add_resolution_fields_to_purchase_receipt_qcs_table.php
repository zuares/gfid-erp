<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Tahap 9 — Migration additive: tambah resolution fields ke purchase_receipt_qcs.
 * Tidak drop kolom, tidak rename, idempoten.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('purchase_receipt_qcs')) {
            return; // tabel belum ada, skip
        }

        Schema::table('purchase_receipt_qcs', function (Blueprint $table) {
            // resolution_type: retur | klaim_invoice | terima_selisih | write_off
            if (!Schema::hasColumn('purchase_receipt_qcs', 'resolution_type')) {
                $table->string('resolution_type', 30)->nullable()->after('notes');
            }

            if (!Schema::hasColumn('purchase_receipt_qcs', 'resolution_notes')) {
                $table->text('resolution_notes')->nullable()->after('resolution_type');
            }

            if (!Schema::hasColumn('purchase_receipt_qcs', 'resolved_at')) {
                $table->timestamp('resolved_at')->nullable()->after('resolution_notes');
            }

            // FK ke purchase_returns — nullable, nullOnDelete (soft link)
            if (!Schema::hasColumn('purchase_receipt_qcs', 'purchase_return_id')) {
                $table->unsignedBigInteger('purchase_return_id')->nullable()->after('resolved_at');
                // FK hanya ditambah jika tabel purchase_returns sudah ada
                if (Schema::hasTable('purchase_returns')) {
                    $table->foreign('purchase_return_id')
                        ->references('id')
                        ->on('purchase_returns')
                        ->nullOnDelete();
                }
            }
        });
    }

    public function down(): void
    {
        if (!Schema::hasTable('purchase_receipt_qcs')) {
            return;
        }

        Schema::table('purchase_receipt_qcs', function (Blueprint $table) {
            // Drop FK dulu jika ada
            try {
                $table->dropForeign(['purchase_return_id']);
            } catch (\Throwable) {}

            $cols = ['purchase_return_id', 'resolved_at', 'resolution_notes', 'resolution_type'];
            foreach ($cols as $col) {
                if (Schema::hasColumn('purchase_receipt_qcs', $col)) {
                    $table->dropColumn($col);
                }
            }
        });
    }
};
