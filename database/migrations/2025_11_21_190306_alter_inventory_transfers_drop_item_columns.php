<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('inventory_transfers', function (Blueprint $table) {

            // Hapus foreign key jika ada (SQLite ignore error)
            try { $table->dropForeign(['item_id']);} catch (\Throwable $e) {}

            // Hapus kolom item_id
            if (Schema::hasColumn('inventory_transfers', 'item_id')) {
                $table->dropColumn('item_id');
            }

            // Hapus kolom qty kalau masih ada
            if (Schema::hasColumn('inventory_transfers', 'qty')) {
                $table->dropColumn('qty');
            }
        });
    }

    public function down(): void
    {
        Schema::table('inventory_transfers', function (Blueprint $table) {
            if (!Schema::hasColumn('inventory_transfers', 'item_id')) {
                $table->unsignedBigInteger('item_id')->nullable();
            }
            if (!Schema::hasColumn('inventory_transfers', 'qty')) {
                $table->decimal('qty', 15, 3)->nullable();
            }
        });
    }
};
