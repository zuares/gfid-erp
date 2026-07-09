<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class InventoryStock extends Model
{
    protected $fillable = [
        'warehouse_id',
        'item_id',
        'lot_id',
        'qty',
        'allocated_qty',
    ];

    public function getAvailableQtyAttribute()
    {
        return $this->qty - $this->allocated_qty;
    }

    public function warehouse()
    {
        return $this->belongsTo(Warehouse::class);
    }

    public function item()
    {
        return $this->belongsTo(Item::class);
    }
}
