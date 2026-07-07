<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Field pendukung WIP Normalization / WIP Cleanup.
 *
 * Prinsip (docs/AUDIT_WIP_PRODUCTION.md bagian G): PAKAI ULANG
 * inventory_adjustments + _lines sebagai record ber-approval, cukup tambah
 * kolom yang belum ada. TIDAK membuat tabel stok baru.
 *
 * Semua aditif & nullable → aman untuk data lama, tidak mengubah perilaku
 * adjustment existing.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('inventory_adjustments', function (Blueprint $table) {
            if (! Schema::hasColumn('inventory_adjustments', 'action')) {
                // keep_open|move|finish|repair|reject|write_off|link_bundle|close_legacy|normalize
                $table->string('action')->nullable()->after('purpose');
            }
            if (! Schema::hasColumn('inventory_adjustments', 'process_date')) {
                $table->date('process_date')->nullable()->after('action');
            }
            if (! Schema::hasColumn('inventory_adjustments', 'from_location_id')) {
                $table->foreignId('from_location_id')->nullable()->after('warehouse_id')
                    ->constrained('warehouses')->nullOnDelete();
            }
            if (! Schema::hasColumn('inventory_adjustments', 'to_location_id')) {
                $table->foreignId('to_location_id')->nullable()->after('from_location_id')
                    ->constrained('warehouses')->nullOnDelete();
            }
            if (! Schema::hasColumn('inventory_adjustments', 'is_legacy')) {
                $table->boolean('is_legacy')->default(false)->after('process_date');
            }
        });

        Schema::table('inventory_adjustment_lines', function (Blueprint $table) {
            if (! Schema::hasColumn('inventory_adjustment_lines', 'action')) {
                $table->string('action')->nullable()->after('direction');
            }
            if (! Schema::hasColumn('inventory_adjustment_lines', 'process_date')) {
                $table->date('process_date')->nullable()->after('action');
            }
            if (! Schema::hasColumn('inventory_adjustment_lines', 'operator_id')) {
                $table->foreignId('operator_id')->nullable()->after('process_date')
                    ->constrained('employees')->nullOnDelete();
            }
            if (! Schema::hasColumn('inventory_adjustment_lines', 'qty_physical')) {
                // hasil hitung fisik saat normalisasi (qty_before=system, qty_after=hasil)
                $table->decimal('qty_physical', 14, 3)->nullable()->after('qty_after');
            }
        });
    }

    public function down(): void
    {
        Schema::table('inventory_adjustment_lines', function (Blueprint $table) {
            foreach (['qty_physical', 'process_date', 'action'] as $col) {
                if (Schema::hasColumn('inventory_adjustment_lines', $col)) {
                    $table->dropColumn($col);
                }
            }
            if (Schema::hasColumn('inventory_adjustment_lines', 'operator_id')) {
                $table->dropConstrainedForeignId('operator_id');
            }
        });

        Schema::table('inventory_adjustments', function (Blueprint $table) {
            foreach (['action', 'process_date', 'is_legacy'] as $col) {
                if (Schema::hasColumn('inventory_adjustments', $col)) {
                    $table->dropColumn($col);
                }
            }
            foreach (['from_location_id', 'to_location_id'] as $col) {
                if (Schema::hasColumn('inventory_adjustments', $col)) {
                    $table->dropConstrainedForeignId($col);
                }
            }
        });
    }
};
