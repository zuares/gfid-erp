<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // 1) bersihin duplikat dulu (kalau ada) sebelum bikin unique index
        // keep row dengan id terkecil, hapus sisanya
        DB::statement("
            DELETE FROM daily_item_sales
            WHERE id NOT IN (
                SELECT MIN(id)
                FROM daily_item_sales
                GROUP BY date, item_id
            )
        ");

        // 2) baru tambah unique index
        Schema::table('daily_item_sales', function (Blueprint $table) {
            $table->unique(['date', 'item_id'], 'daily_item_sales_date_item_unique');
        });
    }

    public function down(): void
    {
        Schema::table('daily_item_sales', function (Blueprint $table) {
            $table->dropUnique('daily_item_sales_date_item_unique');
        });
    }
};
