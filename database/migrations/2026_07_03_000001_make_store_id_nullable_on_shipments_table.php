<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('shipments', function (Blueprint $table) {
            // Shipment bisa dibuat tanpa store — store diisi nanti via rekonsiliasi no pesanan
            $table->unsignedBigInteger('store_id')->nullable()->change();
        });
    }

    public function down(): void
    {
        Schema::table('shipments', function (Blueprint $table) {
            $table->unsignedBigInteger('store_id')->nullable(false)->change();
        });
    }
};
