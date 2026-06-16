<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Tahap 9 — Migration additive: tambah qc_id dan return_reason ke purchase_returns.
 * Tidak drop kolom, tidak rename, idempoten.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('purchase_returns')) {
            return;
        }

        Schema::table('purchase_returns', function (Blueprint $table) {
            // qc_id: link balik ke QC yang memicu return ini (nullable)
            if (!Schema::hasColumn('purchase_returns', 'qc_id')) {
                $table->unsignedBigInteger('qc_id')->nullable()->after('notes');
                if (Schema::hasTable('purchase_receipt_qcs')) {
                    $table->foreign('qc_id')
                        ->references('id')
                        ->on('purchase_receipt_qcs')
                        ->nullOnDelete();
                }
            }

            // return_reason: label alasan retur (dari issue_type QC, opsional)
            if (!Schema::hasColumn('purchase_returns', 'return_reason')) {
                $table->string('return_reason', 100)->nullable()->after('qc_id');
            }
        });
    }

    public function down(): void
    {
        if (!Schema::hasTable('purchase_returns')) {
            return;
        }

        Schema::table('purchase_returns', function (Blueprint $table) {
            try {
                $table->dropForeign(['qc_id']);
            } catch (\Throwable) {}

            foreach (['return_reason', 'qc_id'] as $col) {
                if (Schema::hasColumn('purchase_returns', $col)) {
                    $table->dropColumn($col);
                }
            }
        });
    }
};
