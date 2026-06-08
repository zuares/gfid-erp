<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class MarketplaceOrderItem extends Model
{
    protected $fillable = [
        'hpp_total_snapshot',
        'hpp_unit_snapshot',
        'line_net_amount',
        'line_gross_amount',
        'line_discount',
        'price_after_discount',
        'price_original',
        'variant_snapshot',
        'item_name_snapshot',
        'item_code_snapshot',
        'item_id',
        'external_sku',
        'line_no',
        'order_id',
        'marketplace_order_id',
        'external_item_id',
        'external_model_id',
        'item_name',
        'item_sku',
        'model_sku',
        'variant_name',
        'qty',
        'price',
        'image_url',
        'raw_json',
    ];

    protected $casts = [
        'price' => 'decimal:2',
        'raw_json' => 'array',
    ];

    public function order(): BelongsTo
    {
        return $this->belongsTo(MarketplaceOrder::class, 'marketplace_order_id');
    }
}
