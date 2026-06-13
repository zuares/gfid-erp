<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

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
        'is_split_parent',
        'split_parent_id',
        'stock_available',
        'notes',
        'picked_at',
        'pick_problem',
    ];

    protected $casts = [
        'substituted'     => 'boolean',
        'is_split_parent' => 'boolean',
        'qty_ordered'     => 'integer',
        'qty_fulfilled'   => 'integer',
        'stock_available' => 'integer',
        'picked_at'       => 'datetime',
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

    /** Split children dari line ini. */
    public function splitChildren(): HasMany
    {
        return $this->hasMany(OrderFulfillmentLine::class, 'split_parent_id');
    }

    /** Parent asli (jika ini adalah split child). */
    public function splitParent(): BelongsTo
    {
        return $this->belongsTo(OrderFulfillmentLine::class, 'split_parent_id');
    }

    // ── Helpers ───────────────────────────────────────────────────────────

    public function isResolved(): bool    { return $this->item_id !== null; }
    public function hasShortage(): bool   { return $this->qty_fulfilled < $this->qty_ordered; }
    public function isPicked(): bool      { return $this->picked_at !== null; }
    public function hasProblem(): bool    { return $this->pick_problem !== null; }
    public function isSplitParent(): bool { return (bool) $this->is_split_parent; }
    public function isSplitChild(): bool  { return $this->split_parent_id !== null; }

    public function stockStatus(): string
    {
        if (! $this->isResolved())                           return 'unresolved';
        if ($this->stock_available <= 0)                     return 'empty';
        if ($this->stock_available < $this->qty_ordered)     return 'low';
        return 'ok';
    }
}
