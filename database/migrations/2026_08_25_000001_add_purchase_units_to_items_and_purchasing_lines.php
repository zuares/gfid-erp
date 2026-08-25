<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('items', function (Blueprint $table) {
            $table->string('stock_unit', 20)->nullable()->after('unit');
            $table->string('purchase_unit', 20)->nullable()->after('stock_unit');
            $table->decimal('purchase_conversion_factor', 18, 6)->nullable()->after('purchase_unit');
        });

        DB::table('items')->whereNull('stock_unit')->update(['stock_unit' => DB::raw('unit')]);
        DB::table('items')->whereNull('purchase_unit')->update(['purchase_unit' => DB::raw('unit')]);
        DB::table('items')->whereNull('purchase_conversion_factor')->update(['purchase_conversion_factor' => 1]);

        Schema::table('purchase_order_lines', function (Blueprint $table) {
            $table->string('purchase_unit', 20)->nullable()->after('qty');
            $table->string('stock_unit', 20)->nullable()->after('purchase_unit');
            $table->decimal('conversion_factor', 18, 6)->nullable()->after('stock_unit');
        });

        Schema::table('purchase_receipt_lines', function (Blueprint $table) {
            $table->string('purchase_unit', 20)->nullable()->after('unit');
            $table->string('stock_unit', 20)->nullable()->after('purchase_unit');
            $table->decimal('conversion_factor', 18, 6)->nullable()->after('stock_unit');
            $table->decimal('stock_qty_received', 18, 6)->nullable()->after('qty_received');
            $table->decimal('stock_qty_reject', 18, 6)->nullable()->after('qty_reject');
        });

        // Transaction lines intentionally remain nullable. Older documents
        // must keep their original history; application fallbacks treat a
        // missing snapshot as the legacy 1:1 unit conversion.

        Schema::table('purchase_return_lines', function (Blueprint $table) {
            $table->string('purchase_unit', 20)->nullable()->after('qty');
            $table->string('stock_unit', 20)->nullable()->after('purchase_unit');
            $table->decimal('conversion_factor', 18, 6)->nullable()->after('stock_unit');
            $table->decimal('stock_qty', 18, 6)->nullable()->after('conversion_factor');
        });

        Schema::table('purchase_request_lines', function (Blueprint $table) {
            $table->string('purchase_unit', 20)->nullable()->after('qty');
            $table->string('stock_unit', 20)->nullable()->after('purchase_unit');
            $table->decimal('conversion_factor', 18, 6)->nullable()->after('stock_unit');
        });
    }

    public function down(): void
    {
        Schema::table('purchase_receipt_lines', function (Blueprint $table) {
            $table->dropColumn(['purchase_unit', 'stock_unit', 'conversion_factor', 'stock_qty_received', 'stock_qty_reject']);
        });
        Schema::table('purchase_order_lines', function (Blueprint $table) {
            $table->dropColumn(['purchase_unit', 'stock_unit', 'conversion_factor']);
        });
        Schema::table('purchase_return_lines', function (Blueprint $table) {
            $table->dropColumn(['purchase_unit', 'stock_unit', 'conversion_factor', 'stock_qty']);
        });
        Schema::table('purchase_request_lines', function (Blueprint $table) {
            $table->dropColumn(['purchase_unit', 'stock_unit', 'conversion_factor']);
        });
        Schema::table('items', function (Blueprint $table) {
            $table->dropColumn(['stock_unit', 'purchase_unit', 'purchase_conversion_factor']);
        });
    }
};
