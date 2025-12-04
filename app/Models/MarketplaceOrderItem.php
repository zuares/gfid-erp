<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class MarketplaceOrderItem extends Model
{
    protected $fillable = [
        'order_id',
        'line_no',
        'external_item_id',
        'external_sku',
        'item_id',
        'item_code_snapshot',
        'item_name_snapshot',
        'variant_snapshot',
        'qty',
        'price_original',
        'price_after_discount',
        'line_discount',
        'line_gross_amount',
        'line_net_amount',
        'hpp_unit_snapshot',
        'hpp_total_snapshot',
    ];

    public function order()
    {
        return $this->belongsTo(MarketplaceOrder::class, 'order_id');
    }

    public function item()
    {
        return $this->belongsTo(Item::class, 'item_id');
    }
}
