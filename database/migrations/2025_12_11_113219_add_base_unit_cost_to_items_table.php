<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Tambah kolom base_unit_cost ke tabel items.
     * - Nullable + default(0) supaya aman di production.
     * - Tidak menghapus / mengubah data existing.
     */
    public function up(): void
    {
        Schema::table('items', function (Blueprint $table) {
            // Guard kecil supaya nggak error kalau pernah dibuat manual
            if (!Schema::hasColumn('items', 'base_unit_cost')) {
                $table->decimal('base_unit_cost', 18, 2)
                    ->nullable()
                    ->default(0)
                    ->after('default_uom'); // SESUAIKAN dengan kolom kamu, kalau nggak ada ganti ke after('name_kolom_lain')
            }
        });
    }

    /**
     * Rollback: hapus kolom base_unit_cost.
     */
    public function down(): void
    {
        Schema::table('items', function (Blueprint $table) {
            if (Schema::hasColumn('items', 'base_unit_cost')) {
                $table->dropColumn('base_unit_cost');
            }
        });
    }
};
