<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('storefront_product_variants', function (Blueprint $table) {
            if (! Schema::hasColumn('storefront_product_variants', 'item_id')) {
                $table->foreignId('item_id')
                    ->nullable()
                    ->after('product_id')
                    ->constrained('items')
                    ->nullOnDelete();
            }

            if (! Schema::hasColumn('storefront_product_variants', 'size_label')) {
                $table->string('size_label', 20)->nullable()->after('hex_color');
            }

            if (! Schema::hasColumn('storefront_product_variants', 'stock_override')) {
                $table->unsignedInteger('stock_override')->nullable()->after('price_override');
            }
        });
    }

    public function down(): void
    {
        Schema::table('storefront_product_variants', function (Blueprint $table) {
            if (Schema::hasColumn('storefront_product_variants', 'item_id')) {
                $table->dropConstrainedForeignId('item_id');
            }

            if (Schema::hasColumn('storefront_product_variants', 'size_label')) {
                $table->dropColumn('size_label');
            }

            if (Schema::hasColumn('storefront_product_variants', 'stock_override')) {
                $table->dropColumn('stock_override');
            }
        });
    }
};
