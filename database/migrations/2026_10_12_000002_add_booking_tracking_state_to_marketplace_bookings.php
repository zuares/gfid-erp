<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('marketplace_bookings', function (Blueprint $table) {
            $table->string('tracking_logistics_status', 80)->nullable()->after('tracking_number');
            $table->timestamp('tracking_checked_at')->nullable()->after('tracking_logistics_status');
            $table->index(['booking_status', 'tracking_checked_at'], 'mp_bookings_tracking_refresh_idx');
        });
    }

    public function down(): void
    {
        Schema::table('marketplace_bookings', function (Blueprint $table) {
            $table->dropIndex('mp_bookings_tracking_refresh_idx');
            $table->dropColumn(['tracking_logistics_status', 'tracking_checked_at']);
        });
    }
};
