<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('marketplace_orders', function (Blueprint $table) {
            $table->unique(
                ['store_id', 'channel_order_id'],
                'marketplace_orders_store_channel_order_unique'
            );
        });
    }

    public function down(): void
    {
        Schema::table('marketplace_orders', function (Blueprint $table) {
            $table->dropUnique('marketplace_orders_store_channel_order_unique');
        });
    }
};
