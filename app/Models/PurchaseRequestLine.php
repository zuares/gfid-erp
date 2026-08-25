<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PurchaseRequestLine extends Model
{
    protected $fillable = [
        'purchase_request_id',
        'item_id',
        'supplier_id',
        'purchase_order_id',
        'converted_at',
        'qty',
        'purchase_unit',
        'stock_unit',
        'conversion_factor',
        'unit_price',
        'notes',
    ];

    protected $casts = [
        'qty'        => 'float',
        'conversion_factor' => 'decimal:6',
        'unit_price' => 'float',
        'converted_at' => 'datetime',
    ];

    public function effectivePurchaseUnit(): string
    {
        return trim((string) ($this->purchase_unit ?: $this->item?->purchaseUnit() ?: 'pcs'));
    }

    public function effectiveStockUnit(): string
    {
        return trim((string) ($this->stock_unit ?: $this->item?->stockUnit() ?: 'pcs'));
    }

    public function effectiveConversionFactor(): float
    {
        $factor = (float) ($this->conversion_factor ?? $this->item?->purchaseConversionFactor() ?? 1);
        return $factor > 0 ? $factor : 1.0;
    }

    // =========================================================
    // RELATIONSHIPS
    // =========================================================

    public function purchaseRequest(): BelongsTo
    {
        return $this->belongsTo(PurchaseRequest::class, 'purchase_request_id');
    }

    public function item(): BelongsTo
    {
        return $this->belongsTo(Item::class);
    }

    public function supplier(): BelongsTo
    {
        return $this->belongsTo(Supplier::class);
    }

    public function purchaseOrder(): BelongsTo
    {
        return $this->belongsTo(PurchaseOrder::class);
    }
}
