<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('marketplace_order_settlements', function (Blueprint $table): void {
            $table->decimal('shipping_cost_amount', 15, 2)
                ->nullable()
                ->after('actual_shipping_fee');
        });
    }

    public function down(): void
    {
        Schema::table('marketplace_order_settlements', function (Blueprint $table): void {
            $table->dropColumn('shipping_cost_amount');
        });
    }
};
