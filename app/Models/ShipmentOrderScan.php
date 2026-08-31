<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ShipmentOrderScan extends Model
{
    use HasFactory;

    protected $fillable = [
        'shipment_id',
        'shipment_wave_id',
        'fulfillment_id',
        'order_no',
        'status',
        'source',
        'match_method',
        'match_reason',
        'matched_at',
        'raw_payload',
        'confirmed_at',
        'confirmed_by',
    ];

    protected $casts = [
        'raw_payload' => 'array',
        'confirmed_at' => 'datetime',
        'matched_at' => 'datetime',
    ];

    public function shipment()
    {
        return $this->belongsTo(Shipment::class);
    }

    public function wave()
    {
        return $this->belongsTo(ShipmentWave::class, 'shipment_wave_id');
    }

    public function fulfillment()
    {
        return $this->belongsTo(OrderFulfillment::class, 'fulfillment_id');
    }

    public function lines()
    {
        return $this->hasMany(ShipmentLine::class, 'shipment_order_scan_id');
    }

    public function confirmer()
    {
        return $this->belongsTo(User::class, 'confirmed_by');
    }
}
