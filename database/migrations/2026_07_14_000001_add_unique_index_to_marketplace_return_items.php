<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Jaminan anti-duplikat di level DB untuk item retur.
     * Sebelumnya anti-duplikat hanya mengandalkan updateOrCreate di dalam satu proses,
     * sehingga cron per-jam + Refresh manual yang berjalan bersamaan bisa membuat item dobel.
     */
    public function up(): void
    {
        if (! Schema::hasTable('marketplace_return_items')) {
            return;
        }

        // Bersihkan duplikat lama (jika ada): simpan baris dengan id terkecil
        // per (marketplace_return_id, item_sku, variation_sku).
        DB::statement("
            DELETE FROM marketplace_return_items
            WHERE id NOT IN (
                SELECT keep_id FROM (
                    SELECT MIN(id) AS keep_id
                    FROM marketplace_return_items
                    GROUP BY marketplace_return_id, IFNULL(item_sku, ''), IFNULL(variation_sku, '')
                ) t
            )
        ");

        Schema::table('marketplace_return_items', function (Blueprint $table) {
            $table->unique(
                ['marketplace_return_id', 'item_sku', 'variation_sku'],
                'mp_return_items_unique'
            );
        });
    }

    public function down(): void
    {
        if (! Schema::hasTable('marketplace_return_items')) {
            return;
        }

        Schema::table('marketplace_return_items', function (Blueprint $table) {
            $table->dropUnique('mp_return_items_unique');
        });
    }
};
