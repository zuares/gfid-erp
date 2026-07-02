<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasColumn('storefront_products', 'weight_kg')) {
            return;
        }

        Schema::table('storefront_products', function (Blueprint $table) {
            // Berat per pcs (kg) untuk estimasi ongkir di checkout.
            // Null = pakai fallback setting checkout.weight_per_item.
            $table->decimal('weight_kg', 8, 3)->nullable()->after('base_price');
        });
    }

    public function down(): void
    {
        if (!Schema::hasColumn('storefront_products', 'weight_kg')) {
            return;
        }

        Schema::table('storefront_products', function (Blueprint $table) {
            $table->dropColumn('weight_kg');
        });
    }
};
