<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('daily_item_sales', function (Blueprint $table) {
            // index utama untuk report
            $table->index(['date', 'item_id'], 'dis_date_item_idx');
            $table->index(['item_id', 'date'], 'dis_item_date_idx');
        });
    }

    public function down(): void
    {
        Schema::table('daily_item_sales', function (Blueprint $table) {
            $table->dropIndex('dis_date_item_idx');
            $table->dropIndex('dis_item_date_idx');
        });
    }
};
