<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PurchaseOrderLine extends Model
{
    use HasFactory;

    protected $fillable = [
        'purchase_order_id',
        'item_id',
        'lot_id',
        'qty',
        'unit_price',
        'discount',
        'line_total',
        'notes',
    ];

    protected $casts = [
        'qty' => 'decimal:2',
        'unit_price' => 'decimal:2',
        'discount' => 'decimal:2',
        'line_total' => 'decimal:2',
    ];

    /* ==========================
     *  RELATIONSHIPS
     * ==========================
     */

    public function purchaseOrder()
    {
        return $this->belongsTo(PurchaseOrder::class);
    }

    public function item()
    {
        return $this->belongsTo(Item::class);
    }

    public function lot()
    {
        return $this->belongsTo(Lot::class);
    }

    public function receiveLines()
    {
        return $this->hasMany(PurchaseReceiveLine::class);
    }

    public function receiptLines()
    {
        return $this->hasMany(PurchaseReceiptLine::class, 'purchase_order_line_id');
    }

    public function draftReceiptLines()
    {
        return $this->hasMany(PurchaseReceiptLine::class, 'purchase_order_line_id')
            ->whereHas('receipt', function ($q) {
                $q->where('status', 'draft');
            });
    }

}
