<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('storefront_orders', function (Blueprint $table) {
            if (!Schema::hasColumn('storefront_orders', 'unique_code')) {
                $table->unsignedInteger('unique_code')->default(0)->after('shipping_cost');
            }
        });
    }

    public function down(): void
    {
        Schema::table('storefront_orders', function (Blueprint $table) {
            if (Schema::hasColumn('storefront_orders', 'unique_code')) {
                $table->dropColumn('unique_code');
            }
        });
    }
};
