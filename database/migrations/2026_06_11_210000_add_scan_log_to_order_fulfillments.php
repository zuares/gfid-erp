<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('order_fulfillments', function (Blueprint $table) {
            $table->json('scan_log')->nullable()->after('notes')
                ->comment('Raw scan list saat pack: [{item_id, code, name, qty}]');
        });
    }

    public function down(): void
    {
        Schema::table('order_fulfillments', function (Blueprint $table) {
            $table->dropColumn('scan_log');
        });
    }
};
