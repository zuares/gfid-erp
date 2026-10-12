<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('marketplace_orders', function (Blueprint $table) {
            $table->string('purchase_channel', 32)->nullable()->index();
        });

        Schema::table('mp_shipments', function (Blueprint $table) {
            $table->string('purchase_channel', 32)->nullable()->index();
        });
    }

    public function down(): void
    {
        Schema::table('mp_shipments', function (Blueprint $table) {
            $table->dropColumn('purchase_channel');
        });

        Schema::table('marketplace_orders', function (Blueprint $table) {
            $table->dropColumn('purchase_channel');
        });
    }
};
