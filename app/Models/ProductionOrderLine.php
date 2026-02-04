<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ProductionOrderLine extends Model
{
    protected $fillable = [
        'production_order_id',
        'item_id',
        'qty_target',
    ];

    // optional tapi bagus
    public function order()
    {
        return $this->belongsTo(ProductionOrder::class, 'production_order_id');
    }

    public function item()
    {
        return $this->belongsTo(Item::class);
    }
}
