<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ShipmentReturnOrderScanItem extends Model
{
    use HasFactory;

    protected $fillable = [
        'shipment_return_order_scan_id',
        'item_id',
        'shipment_return_line_id',
        'matched_order_item_id',
        'qty_scanned',
        'qty_expected',
        'match_status',
        'raw_payload',
    ];

    protected $casts = [
        'raw_payload' => 'array',
    ];

    public function orderScan()
    {
        return $this->belongsTo(ShipmentReturnOrderScan::class, 'shipment_return_order_scan_id');
    }

    public function item()
    {
        return $this->belongsTo(Item::class);
    }

    public function shipmentReturnLine()
    {
        return $this->belongsTo(ShipmentReturnLine::class);
    }
}
