<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SewingPickupLineSupplyLine extends Model
{
    protected $fillable = [
        'sewing_pickup_id',
        'sewing_pickup_line_id',
        'material_item_id',
        'required_qty',
        'issued_qty',
        'uom',
        'notes',
    ];

    protected $casts = [
        'required_qty' => 'float',
        'issued_qty' => 'float',
    ];

    public function sewingPickup()
    {
        return $this->belongsTo(SewingPickup::class, 'sewing_pickup_id');
    }

    public function sewingPickupLine()
    {
        return $this->belongsTo(SewingPickupLine::class, 'sewing_pickup_line_id');
    }

    public function materialItem()
    {
        return $this->belongsTo(Item::class, 'material_item_id');
    }
}
