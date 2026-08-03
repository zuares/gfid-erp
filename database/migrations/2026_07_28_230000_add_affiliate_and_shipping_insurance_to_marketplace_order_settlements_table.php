<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('marketplace_order_settlements', function (Blueprint $table) {
            $table->decimal('affiliate_fee', 15, 2)->default(0)->after('transaction_fee');
            $table->decimal('shipping_insurance_fee', 15, 2)->default(0)->after('reverse_shipping_fee');
        });
    }

    public function down(): void
    {
        Schema::table('marketplace_order_settlements', function (Blueprint $table) {
            $table->dropColumn(['affiliate_fee', 'shipping_insurance_fee']);
        });
    }
};
