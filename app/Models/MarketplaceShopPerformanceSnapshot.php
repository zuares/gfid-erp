<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class MarketplaceShopPerformanceSnapshot extends Model
{
    protected $table = 'marketplace_shop_performance_snapshots';

    protected $fillable = [
        'store_id', 'shop_id', 'principal_id', 'granularity',
        'period_start', 'period_end', 'timezone', 'currency',
        'sales', 'orders', 'units_sold', 'average_basket_size',
        'items_per_order', 'average_selling_price', 'product_clicks',
        'product_views', 'unique_visitors', 'item_conversion_rate',
        'order_conversion_rate', 'atp_top_skus_l1d', 'atp_top_skus_l30d',
        'atp_live_skus_l1d', 'atp_live_skus_l30d', 'flash_sale_sales',
        'flash_sale_orders', 'flash_sale_units_sold', 'voucher_sales',
        'voucher_buyers', 'voucher_usage_rate', 'voucher_cir', 'voucher_cost',
        'request_id', 'metrics_json', 'raw_response', 'fetched_at',
    ];

    protected $casts = [
        'period_start' => 'date',
        'period_end' => 'date',
        'metrics_json' => 'array',
        'raw_response' => 'array',
        'fetched_at' => 'datetime',
        'sales' => 'decimal:6',
        'average_basket_size' => 'decimal:6',
        'items_per_order' => 'decimal:8',
        'average_selling_price' => 'decimal:6',
        'item_conversion_rate' => 'decimal:10',
        'order_conversion_rate' => 'decimal:10',
        'atp_top_skus_l1d' => 'decimal:10',
        'atp_top_skus_l30d' => 'decimal:10',
        'atp_live_skus_l1d' => 'decimal:10',
        'atp_live_skus_l30d' => 'decimal:10',
        'flash_sale_sales' => 'decimal:6',
        'voucher_sales' => 'decimal:6',
        'voucher_usage_rate' => 'decimal:10',
        'voucher_cir' => 'decimal:10',
        'voucher_cost' => 'decimal:6',
    ];

    public function store(): BelongsTo
    {
        return $this->belongsTo(Store::class);
    }
}
