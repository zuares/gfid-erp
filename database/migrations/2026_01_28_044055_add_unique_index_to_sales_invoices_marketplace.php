<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('sales_invoices', function (Blueprint $table) {
            // pastikan kolom nullable → OK untuk unique composite
            $table->unique(
                ['store_id', 'channel', 'channel_order_no'],
                'sales_invoices_store_channel_order_unique'
            );
        });
    }

    public function down(): void
    {
        Schema::table('sales_invoices', function (Blueprint $table) {
            $table->dropUnique('sales_invoices_store_channel_order_unique');
        });
    }
};
