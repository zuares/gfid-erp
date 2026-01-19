<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('supplier_items')) {
            // ✅ dev sqlite belum ada tabelnya, skip
            return;
        }

        Schema::table('supplier_items', function (Blueprint $table) {
            // SQLite akan error kalau index sudah ada, jadi amanin dengan try/catch? (tidak bisa di sini)
            // Minimal: cek column dulu
            if (!Schema::hasColumn('supplier_items', 'supplier_id')) {
                return;
            }

            $table->index('supplier_id');
            $table->index('item_id');
            $table->unique(['supplier_id', 'item_id'], 'supplier_items_unique');
        });
    }

    public function down(): void
    {
        if (!Schema::hasTable('supplier_items')) {
            return;
        }

        Schema::table('supplier_items', function (Blueprint $table) {
            // drop unique/index (kalau ada)
            $table->dropUnique('supplier_items_unique');
            $table->dropIndex(['supplier_id']);
            $table->dropIndex(['item_id']);
        });
    }
};
