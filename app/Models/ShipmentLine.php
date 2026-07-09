<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ShipmentLine extends Model
{
    use HasFactory;

    protected $fillable = [
        'shipment_id',
        'item_id',
        'qty_scanned',
        'allocated_qty',
        'notes',
    ];

    public function shipment()
    {
        return $this->belongsTo(Shipment::class);
    }

    public function item()
    {
        return $this->belongsTo(Item::class);
    }
}
