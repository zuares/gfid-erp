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
        'qty',
        'hpp_unit_snapshot',
        'sales_invoice_line_id',
        'remarks',
    ];

    protected $casts = [
        'hpp_unit_snapshot' => 'decimal:4',
    ];

    /*
    |--------------------------------------------------------------------------
    | Relationships
    |--------------------------------------------------------------------------
     */

    public function shipment()
    {
        return $this->belongsTo(Shipment::class);
    }

    public function item()
    {
        return $this->belongsTo(Item::class);
    }

    public function salesInvoiceLine()
    {
        return $this->belongsTo(SalesInvoiceLine::class);
    }
}
