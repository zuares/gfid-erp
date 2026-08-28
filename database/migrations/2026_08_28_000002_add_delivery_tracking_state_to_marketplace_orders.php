<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('marketplace_orders', function (Blueprint $table) {
            $table->boolean('delivery_failed')->default(false)->index()->after('shipping_awb_no');
            $table->timestamp('delivery_failed_at')->nullable()->after('delivery_failed');
            $table->string('tracking_status', 80)->nullable()->after('delivery_failed_at');
            $table->text('tracking_description')->nullable()->after('tracking_status');
            $table->timestamp('tracking_checked_at')->nullable()->index()->after('tracking_description');
        });
    }

    public function down(): void
    {
        Schema::table('marketplace_orders', function (Blueprint $table) {
            $table->dropIndex(['delivery_failed']);
            $table->dropIndex(['tracking_checked_at']);
            $table->dropColumn([
                'delivery_failed',
                'delivery_failed_at',
                'tracking_status',
                'tracking_description',
                'tracking_checked_at',
            ]);
        });
    }
};
