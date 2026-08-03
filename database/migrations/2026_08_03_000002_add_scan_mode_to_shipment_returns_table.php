<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasColumn('shipment_returns', 'scan_mode')) {
            Schema::table('shipment_returns', function (Blueprint $table) {
                $table->string('scan_mode', 20)->default('order_first')->after('status');
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasColumn('shipment_returns', 'scan_mode')) {
            Schema::table('shipment_returns', function (Blueprint $table) {
                $table->dropColumn('scan_mode');
            });
        }
    }
};
