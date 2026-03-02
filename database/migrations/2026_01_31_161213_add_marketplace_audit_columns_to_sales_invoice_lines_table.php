<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('sales_invoice_lines', function (Blueprint $table) {
            // Audit marketplace SKU & pack
            if (!Schema::hasColumn('sales_invoice_lines', 'sku_ref')) {
                $table->string('sku_ref', 80)->nullable()->after('item_id');
            }
            if (!Schema::hasColumn('sales_invoice_lines', 'qty_pack')) {
                $table->unsignedInteger('qty_pack')->nullable()->after('sku_ref');
            }
            if (!Schema::hasColumn('sales_invoice_lines', 'pack_qty')) {
                $table->unsignedInteger('pack_qty')->nullable()->after('qty_pack'); // contoh: 6, 12, dst
            }

            // Snapshot nilai asli dari file (buat audit)
            // pakai decimal biar aman di MySQL (numeric juga ok, tapi decimal explicit lebih jelas)
            if (!Schema::hasColumn('sales_invoice_lines', 'unit_price_file')) {
                $table->decimal('unit_price_file', 18, 2)->default(0)->after('unit_price');
            }
            if (!Schema::hasColumn('sales_invoice_lines', 'line_total_file')) {
                $table->decimal('line_total_file', 18, 2)->default(0)->after('line_total');
            }

            // Index untuk lookup cepat saat audit
            $table->index(['sales_invoice_id', 'item_id'], 'sil_inv_item_idx');
            $table->index(['sku_ref'], 'sil_sku_ref_idx');
        });
    }

    public function down(): void
    {
        Schema::table('sales_invoice_lines', function (Blueprint $table) {
            $table->dropIndex('sil_inv_item_idx');
            $table->dropIndex('sil_sku_ref_idx');

            if (Schema::hasColumn('sales_invoice_lines', 'line_total_file')) {
                $table->dropColumn('line_total_file');
            }
            if (Schema::hasColumn('sales_invoice_lines', 'unit_price_file')) {
                $table->dropColumn('unit_price_file');
            }
            if (Schema::hasColumn('sales_invoice_lines', 'pack_qty')) {
                $table->dropColumn('pack_qty');
            }
            if (Schema::hasColumn('sales_invoice_lines', 'qty_pack')) {
                $table->dropColumn('qty_pack');
            }
            if (Schema::hasColumn('sales_invoice_lines', 'sku_ref')) {
                $table->dropColumn('sku_ref');
            }
        });
    }
};
