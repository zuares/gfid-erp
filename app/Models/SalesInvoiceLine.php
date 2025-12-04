<?php
// app/Models/SalesInvoiceLine.php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SalesInvoiceLine extends Model
{
    protected $fillable = [
        'sales_invoice_id',
        'line_no',
        'item_id',
        'item_code_snapshot',
        'item_name_snapshot',
        'qty',
        'unit_price',
        'line_discount',
        'line_total',
        'hpp_unit_snapshot',
        'hpp_total_snapshot',
        'warehouse_id',
        'lot_id',
    ];

    protected $casts = [
        'qty' => 'decimal:4',
        'unit_price' => 'decimal:2',
        'line_discount' => 'decimal:2',
        'line_total' => 'decimal:2',
        'hpp_unit_snapshot' => 'decimal:4',
        'hpp_total_snapshot' => 'decimal:2',
    ];

    public function invoice()
    {
        return $this->belongsTo(SalesInvoice::class, 'sales_invoice_id');
    }

    public function item()
    {
        return $this->belongsTo(Item::class);
    }

    public function warehouse()
    {
        return $this->belongsTo(Warehouse::class);
    }

    public function lot()
    {
        return $this->belongsTo(Lot::class);
    }
}
