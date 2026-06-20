<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('supplier_items', function (Blueprint $table) {
            $table->boolean('is_primary')->default(false)->after('item_id');
            $table->decimal('minimum_order_qty', 18, 4)->nullable()->after('last_price');
            $table->unsignedInteger('lead_time_days')->nullable()->after('minimum_order_qty');
            $table->boolean('active')->default(true)->after('lead_time_days');
            $table->index(['item_id', 'is_primary', 'active'], 'supplier_items_recommendation_idx');
        });
    }

    public function down(): void
    {
        Schema::table('supplier_items', function (Blueprint $table) {
            $table->dropIndex('supplier_items_recommendation_idx');
            $table->dropColumn(['is_primary', 'minimum_order_qty', 'lead_time_days', 'active']);
        });
    }
};
