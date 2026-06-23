<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('sales_invoices', function (Blueprint $table) {
            $table->string('channel', 30)->nullable()->index();
            $table->string('channel_order_no', 120)->nullable()->index();
            $table->dateTime('paid_at')->nullable()->index();
            $table->dateTime('completed_at')->nullable()->index();
            $table->string('marketplace_status', 30)->nullable()->index();
            $table->string('awb', 80)->nullable()->index();

            $table->index(['store_id', 'channel_order_no'], 'si_store_order_lookup');
        });
    }

    public function down(): void
    {
        Schema::table('sales_invoices', function (Blueprint $table) {
            $table->dropIndex('si_store_order_lookup');
            $table->dropColumn(['channel', 'channel_order_no', 'paid_at', 'completed_at', 'marketplace_status', 'awb']);
        });
    }
};
