<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PurchaseReceiptLine extends Model
{
    protected $fillable = [
        'purchase_receipt_id',
        'purchase_order_line_id', // ← penting
        'purchase_return_line_id',
        'item_id',
        'lot_id',
        'qty_received',
        'qty_reject',
        'unit',
        'unit_price',
        'line_total',
        'allocation',
        'expense_account_id',
        'notes',
    ];

    protected $casts = [
        // float bukan decimal:3 — hindari "5.000" yg disalah-baca num() sebagai 5000
        'qty_received' => 'float',
        'qty_reject'   => 'float',
        'unit_price'   => 'decimal:2',
        'line_total'   => 'decimal:2',
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

    public function expenseAccount()
    {
        return $this->belongsTo(Account::class, 'expense_account_id');
    }

    public function returnLineOrigin()
    {
        return $this->belongsTo(PurchaseReturnLine::class, 'purchase_return_line_id');
    }
}
