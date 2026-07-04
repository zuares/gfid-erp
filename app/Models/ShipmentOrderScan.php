<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ShipmentOrderScan extends Model
{
    use HasFactory;

    protected $fillable = [
        'shipment_id',
        'order_no',
        'status',
        'source',
        'raw_payload',
        'confirmed_at',
        'confirmed_by',
    ];

    protected $casts = [
        'raw_payload' => 'array',
        'confirmed_at' => 'datetime',
    ];

    public function shipment()
    {
        return $this->belongsTo(Shipment::class);
    }

    public function confirmer()
    {
        return $this->belongsTo(User::class, 'confirmed_by');
    }
}
