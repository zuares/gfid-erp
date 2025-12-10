<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Tambah kolom:
     * - stock_opnames.type (periodic / opening)
     * - stock_opname_lines.unit_cost (HPP per unit, untuk opening balance)
     */
    public function up(): void
    {
        // 1) Tambah kolom type di stock_opnames
        if (Schema::hasTable('stock_opnames') && !Schema::hasColumn('stock_opnames', 'type')) {
            Schema::table('stock_opnames', function (Blueprint $table) {
                // default periodic supaya semua data lama tetap valid
                $table->string('type', 20)
                    ->default('periodic')
                    ->after('code');

                $table->index('type');
            });
        }

        // 2) Tambah kolom unit_cost di stock_opname_lines
        if (Schema::hasTable('stock_opname_lines') && !Schema::hasColumn('stock_opname_lines', 'unit_cost')) {
            Schema::table('stock_opname_lines', function (Blueprint $table) {
                // nullable & tanpa default → tidak ganggu data lama
                $table->decimal('unit_cost', 15, 2)
                    ->nullable()
                    ->after('difference_qty');
            });
        }
    }

    /**
     * Rollback aman: hanya drop kalau kolomnya benar-benar ada.
     */
    public function down(): void
    {
        if (Schema::hasTable('stock_opnames') && Schema::hasColumn('stock_opnames', 'type')) {
            Schema::table('stock_opnames', function (Blueprint $table) {
                $table->dropIndex(['type']);
                $table->dropColumn('type');
            });
        }

        if (Schema::hasTable('stock_opname_lines') && Schema::hasColumn('stock_opname_lines', 'unit_cost')) {
            Schema::table('stock_opname_lines', function (Blueprint $table) {
                $table->dropColumn('unit_cost');
            });
        }
    }
};
