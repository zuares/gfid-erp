<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ShipmentReturnOrderScan extends Model
{
    use HasFactory;

    protected $fillable = [
        'shipment_return_id',
        'order_no',
        'order_number',
        'shipment_id',
        'sales_invoice_id',
        'marketplace_order_id',
        'matched_order_id',
        'status',
        'match_status',
        'source_type',
        'source',
        'raw_payload',
        'matched_at',
        'matched_by',
    ];

    protected $casts = [
        'raw_payload' => 'array',
        'matched_at' => 'datetime',
    ];

    public function shipmentReturn()
    {
        return $this->belongsTo(ShipmentReturn::class);
    }

    public function items()
    {
        return $this->hasMany(ShipmentReturnOrderScanItem::class, 'shipment_return_order_scan_id');
    }

    public function matcher()
    {
        return $this->belongsTo(User::class, 'matched_by');
    }
}
