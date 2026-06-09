<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class OrderFulfillmentLine extends Model
{
    protected $fillable = [
        'fulfillment_id',
        'marketplace_order_item_id',
        'marketplace_sku',
        'marketplace_item_name',
        'item_id',
        'lot_id',
        'qty_ordered',
        'qty_fulfilled',
        'substituted',
        'stock_available',
        'notes',
    ];

    protected $casts = [
        'substituted'     => 'boolean',
        'qty_ordered'     => 'integer',
        'qty_fulfilled'   => 'integer',
        'stock_available' => 'integer',
    ];

    // ── Relations ─────────────────────────────────────────────────────────

    public function fulfillment(): BelongsTo
    {
        return $this->belongsTo(OrderFulfillment::class);
    }

    public function marketplaceOrderItem(): BelongsTo
    {
        return $this->belongsTo(MarketplaceOrderItem::class, 'marketplace_order_item_id');
    }

    public function item(): BelongsTo
    {
        return $this->belongsTo(Item::class);
    }

    public function lot(): BelongsTo
    {
        return $this->belongsTo(Lot::class);
    }

    // ── Helpers ───────────────────────────────────────────────────────────

    public function isResolved(): bool   { return $this->item_id !== null; }
    public function hasShortage(): bool  { return $this->qty_fulfilled < $this->qty_ordered; }
    public function stockStatus(): string
    {
        if (! $this->isResolved())                   return 'unresolved'; // SKU belum dipetakan
        if ($this->stock_available <= 0)             return 'empty';      // stok habis
        if ($this->stock_available < $this->qty_ordered) return 'low';   // stok kurang
        return 'ok';
    }
}
