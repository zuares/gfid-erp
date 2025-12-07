<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PurchaseReceiptLine extends Model
{
    protected $fillable = [
        'purchase_receipt_id',
        'purchase_order_line_id', // ← penting
        'item_id',
        'lot_id',
        'qty_received',
        'qty_reject',
        'unit',
        'unit_price',
        'line_total',
        'notes',
    ];

    protected $casts = [
        'qty_received' => 'decimal:3',
        'qty_reject' => 'decimal:3',
        'unit_price' => 'decimal:2',
        'line_total' => 'decimal:2',
    ];

    public function receipt()
    {
        return $this->belongsTo(PurchaseReceipt::class, 'purchase_receipt_id');
    }

    public function item()
    {
        return $this->belongsTo(Item::class);
    }

    public function purchaseOrderLine()
    {
        return $this->belongsTo(PurchaseOrderLine::class, 'purchase_order_line_id');
    }
}
