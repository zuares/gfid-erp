<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ShipmentOrderScanMarketplaceOrder extends Model
{
    protected $table = 'shipment_order_scan_marketplace_orders';

    public $timestamps = false;

    protected $fillable = [
        'shipment_order_scan_id',
        'marketplace_order_id',
        'fulfillment_id',
    ];

    public function scan(): BelongsTo
    {
        return $this->belongsTo(ShipmentOrderScan::class, 'shipment_order_scan_id');
    }

    public function marketplaceOrder(): BelongsTo
    {
        return $this->belongsTo(MarketplaceOrder::class, 'marketplace_order_id');
    }

    public function fulfillment(): BelongsTo
    {
        return $this->belongsTo(OrderFulfillment::class, 'fulfillment_id');
    }
}
