<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('purchase_orders', function (Blueprint $table) {
            // Status penerimaan barang (terpisah dari status dokumen PO)
            // not_received | partial | fully_received
            $table->string('received_status', 20)
                ->default('not_received')
                ->after('payment_status')
                ->comment('not_received | partial | fully_received');
        });
    }

    public function down(): void
    {
        Schema::table('purchase_orders', function (Blueprint $table) {
            $table->dropColumn('received_status');
        });
    }
};
