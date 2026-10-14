<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('marketplace_shop_performance_snapshots', function (Blueprint $table) {
            $table->id();
            $table->foreignId('store_id')->constrained('stores')->cascadeOnDelete();
            $table->unsignedBigInteger('shop_id');
            $table->unsignedBigInteger('principal_id')->nullable();
            $table->string('granularity', 20);
            $table->date('period_start');
            $table->date('period_end');
            $table->string('timezone', 12)->default('GMT+7');
            $table->string('currency', 8)->default('LOCAL');

            $table->decimal('sales', 20, 6)->default(0);
            $table->unsignedBigInteger('orders')->default(0);
            $table->unsignedBigInteger('units_sold')->default(0);
            $table->decimal('average_basket_size', 20, 6)->default(0);
            $table->decimal('items_per_order', 20, 8)->default(0);
            $table->decimal('average_selling_price', 20, 6)->default(0);
            $table->unsignedBigInteger('product_clicks')->default(0);
            $table->unsignedBigInteger('product_views')->default(0);
            $table->unsignedBigInteger('unique_visitors')->default(0);
            $table->decimal('item_conversion_rate', 20, 10)->default(0);
            $table->decimal('order_conversion_rate', 20, 10)->default(0);
            $table->decimal('atp_top_skus_l1d', 20, 10)->nullable();
            $table->decimal('atp_top_skus_l30d', 20, 10)->nullable();
            $table->decimal('atp_live_skus_l1d', 20, 10)->nullable();
            $table->decimal('atp_live_skus_l30d', 20, 10)->nullable();
            $table->decimal('flash_sale_sales', 20, 6)->default(0);
            $table->unsignedBigInteger('flash_sale_orders')->default(0);
            $table->unsignedBigInteger('flash_sale_units_sold')->default(0);
            $table->decimal('voucher_sales', 20, 6)->default(0);
            $table->unsignedBigInteger('voucher_buyers')->default(0);
            $table->decimal('voucher_usage_rate', 20, 10)->default(0);
            $table->decimal('voucher_cir', 20, 10)->default(0);
            $table->decimal('voucher_cost', 20, 6)->default(0);

            $table->string('request_id')->nullable();
            $table->json('metrics_json')->nullable();
            $table->json('raw_response')->nullable();
            $table->timestamp('fetched_at')->nullable();
            $table->timestamps();

            $table->unique(
                ['store_id', 'shop_id', 'granularity', 'period_start', 'period_end', 'currency'],
                'shop_performance_snapshot_unique'
            );
            $table->index(['store_id', 'period_start', 'period_end']);
            $table->index(['shop_id', 'period_start']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('marketplace_shop_performance_snapshots');
    }
};
