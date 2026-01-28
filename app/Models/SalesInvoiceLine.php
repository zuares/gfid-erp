<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SalesInvoiceLine extends Model
{
    protected $table = 'sales_invoice_lines';

    protected $fillable = [
        'sales_invoice_id',
        'item_id',
        'qty',
        'unit_price',
        'line_discount',
        'line_total',

        // snapshot costing
        'hpp_unit_snapshot',
        'hpp_total_snapshot',

        // margin
        'margin_unit',
        'margin_total',
    ];

    protected $casts = [
        'qty' => 'float',
        'unit_price' => 'float',
        'line_discount' => 'float',
        'line_total' => 'float',

        'hpp_unit_snapshot' => 'float',
        'hpp_total_snapshot' => 'float',

        'margin_unit' => 'float',
        'margin_total' => 'float',
    ];

    public function invoice()
    {
        return $this->belongsTo(SalesInvoice::class, 'sales_invoice_id');
    }

    public function item()
    {
        return $this->belongsTo(Item::class, 'item_id');
    }
}
