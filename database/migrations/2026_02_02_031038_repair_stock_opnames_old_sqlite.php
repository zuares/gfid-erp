<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // kalau tabel old ada, tambahkan kolom yang kurang supaya SELECT gak error
        if (Schema::hasTable('stock_opnames_old')) {
            $cols = collect(DB::select("PRAGMA table_info(stock_opnames_old)"))->pluck('name')->all();

            if (!in_array('cancelled_at', $cols, true)) {
                DB::statement("ALTER TABLE stock_opnames_old ADD COLUMN cancelled_at DATETIME NULL");
            }
            if (!in_array('cancelled_by', $cols, true)) {
                DB::statement("ALTER TABLE stock_opnames_old ADD COLUMN cancelled_by INTEGER NULL");
            }
            if (!in_array('cancel_reason', $cols, true)) {
                DB::statement("ALTER TABLE stock_opnames_old ADD COLUMN cancel_reason VARCHAR NULL");
            }
        }
    }
    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        //
    }
};
