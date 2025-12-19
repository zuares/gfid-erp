<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ShipmentReturnLine extends Model
{
    use HasFactory;

    protected $table = 'shipment_return_lines';

    protected $fillable = [
        'shipment_return_id',
        'item_id',
        'shipment_line_id',
        'qty',
        'remarks',
    ];

    /* ==========================
    RELATIONS
    ========================== */

    public function header()
    {
        return $this->belongsTo(ShipmentReturn::class, 'shipment_return_id');
    }

    public function return ()
    {
        return $this->belongsTo(ShipmentReturn::class, 'shipment_return_id');
    }

    public function item()
    {
        return $this->belongsTo(Item::class);
    }

    public function shipmentLine()
    {
        return $this->belongsTo(ShipmentLine::class);
    }
}
