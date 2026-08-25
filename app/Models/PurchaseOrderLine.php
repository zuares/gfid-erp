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
        'purchase_unit',
        'stock_unit',
        'conversion_factor',
        'unit_price',
        'discount',
        'line_total',
        'notes',
        'allocation',
        'expense_account_id',
    ];

    protected $casts = [
        'qty' => 'decimal:2',
        'conversion_factor' => 'decimal:6',
        'unit_price' => 'decimal:2',
        'discount' => 'decimal:2',
        'line_total' => 'decimal:2',
    ];

    public function effectivePurchaseUnit(): string
    {
        return trim((string) ($this->purchase_unit ?: $this->item?->purchaseUnit() ?: $this->item?->unit ?: 'pcs'));
    }

    public function effectiveStockUnit(): string
    {
        return trim((string) ($this->stock_unit ?: $this->item?->stockUnit() ?: $this->item?->unit ?: 'pcs'));
    }

    public function effectiveConversionFactor(): float
    {
        $factor = (float) ($this->conversion_factor ?? $this->item?->purchaseConversionFactor() ?? 1);
        return $factor > 0 ? $factor : 1.0;
    }

    public function stockQty(): float
    {
        return round((float) $this->qty * $this->effectiveConversionFactor(), 6);
    }

    public function stockUnitPrice(): float
    {
        return (float) $this->unit_price / max(0.000001, $this->effectiveConversionFactor());
    }

    public function calculatedLineTotal(): float
    {
        return round(max(0, $this->stockQty() * $this->stockUnitPrice() - (float) $this->discount), 2);
    }

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

    public function expenseAccount()
    {
        return $this->belongsTo(Account::class, 'expense_account_id');
    }

}
