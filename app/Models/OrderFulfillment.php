<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class OrderFulfillment extends Model
{
    public const STATUS_DRAFT          = 'draft';
    public const STATUS_PENDING_REVIEW = 'pending_review';
    public const STATUS_PICKING        = 'picking';   // sedang dipick oleh picker di gudang
    public const STATUS_PACKED         = 'packed';    // semua item sudah dipick, siap dikonfirmasi
    public const STATUS_CONFIRMED      = 'confirmed';
    public const STATUS_CANCELLED      = 'cancelled';

    protected $fillable = [
        'marketplace_order_id',
        'warehouse_id',
        'status',
        'notes',
        'scan_log',
        'confirmed_by',
        'confirmed_at',
    ];

    protected $casts = [
        'confirmed_at' => 'datetime',
    ];

    // ── Relations ──────────────────────────────────────────────────────────

    public function order(): BelongsTo
    {
        return $this->belongsTo(MarketplaceOrder::class, 'marketplace_order_id');
    }

    public function warehouse(): BelongsTo
    {
        return $this->belongsTo(Warehouse::class);
    }

    public function confirmedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'confirmed_by');
    }

    public function lines(): HasMany
    {
        return $this->hasMany(OrderFulfillmentLine::class, 'fulfillment_id');
    }

    // ── Helpers ───────────────────────────────────────────────────────────

    public function isDraft(): bool          { return $this->status === self::STATUS_DRAFT; }
    public function isPendingReview(): bool   { return $this->status === self::STATUS_PENDING_REVIEW; }
    public function isPicking(): bool         { return $this->status === self::STATUS_PICKING; }
    public function isPacked(): bool          { return $this->status === self::STATUS_PACKED; }
    public function isConfirmed(): bool       { return $this->status === self::STATUS_CONFIRMED; }
    public function isCancelled(): bool       { return $this->status === self::STATUS_CANCELLED; }
    public function canConfirm(): bool        { return in_array($this->status, [self::STATUS_DRAFT, self::STATUS_PENDING_REVIEW, self::STATUS_PICKING, self::STATUS_PACKED]); }
    public function canStartPicking(): bool   { return in_array($this->status, [self::STATUS_PENDING_REVIEW]); }
    public function canPack(): bool           { return $this->status === self::STATUS_PICKING; }
    public function canUnpack(): bool         { return $this->status === self::STATUS_PACKED; }

    /** Apakah semua line sudah punya item_id (tidak ada yang unresolved). */
    public function allLinesResolved(): bool
    {
        return $this->lines()->whereNull('item_id')->doesntExist();
    }

    /** Apakah ada line yang stok-nya kurang. */
    public function hasStockShortage(): bool
    {
        return $this->lines()->whereColumn('qty_fulfilled', '<', 'qty_ordered')->exists();
    }

    public function shipmentScans()
    {
        return $this->hasMany(\App\Models\ShipmentOrderScan::class, 'fulfillment_id');
    }
}
