<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SewingPickupSupplyLine extends Model
{
    protected $fillable = [
        'sewing_pickup_id',
        'material_item_id',
        'required_qty',
        'issued_qty',
        'required_pcs',
        'issued_pcs',
        'uom',
        'stock_available_snapshot',
        'pending_cost',
        'issued_unit_cost',
    ];

    protected $casts = [
        'required_qty'             => 'float',
        'issued_qty'               => 'float',
        'required_pcs'             => 'float',
        'issued_pcs'               => 'float',
        'stock_available_snapshot' => 'float',
        'pending_cost'             => 'boolean',
        'issued_unit_cost'         => 'float',
    ];

    public function pickup()
    {
        return $this->belongsTo(SewingPickup::class, 'sewing_pickup_id');
    }

    public function material()
    {
        return $this->belongsTo(Item::class, 'material_item_id');
    }

    public function getShortageQtyAttribute(): float
    {
        return max((float) $this->required_qty - (float) $this->issued_qty, 0);
    }
}
