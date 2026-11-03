<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('inventory_stocks', function (Blueprint $table): void {
            $table->index('item_id', 'inventory_stocks_item_id_index');
        });
    }

    public function down(): void
    {
        Schema::table('inventory_stocks', function (Blueprint $table): void {
            $table->dropIndex('inventory_stocks_item_id_index');
        });
    }
};
