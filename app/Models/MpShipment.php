<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class MpShipment extends Model
{
    protected $table = 'mp_shipments';

    protected $fillable = [
        'store_id',
        'marketplace_order_id',
        'channel',
        'platform_order_id',
        'platform_shipment_id',
        'tracking_no',
        'marketplace_status',
        'status_norm',
        'order_created_at',
        'paid_at',
        'shipped_at',
        'delivered_at',
        'completed_at',
        'total_qty',
        'order_subtotal',
        'discount_total',
        'shipping_fee',
        'grand_total',
        'currency',
        'platform_fee_total',
        'refund_total',
        'net_payout_actual',
        'released_at',
        'import_batch_id',
        'source_file',
        'imported_at',
        'source_type',
        'source_updated_at',
        'raw_payload',
    ];

    protected $casts = [
        'store_id' => 'integer',
        'marketplace_order_id' => 'integer',
        'total_qty' => 'integer',

        'order_subtotal' => 'decimal:2',
        'discount_total' => 'decimal:2',
        'shipping_fee' => 'decimal:2',
        'grand_total' => 'decimal:2',
        'platform_fee_total' => 'decimal:2',
        'refund_total' => 'decimal:2',
        'net_payout_actual' => 'decimal:2',

        'order_created_at' => 'datetime',
        'paid_at' => 'datetime',
        'shipped_at' => 'datetime',
        'delivered_at' => 'datetime',
        'completed_at' => 'datetime',
        'released_at' => 'datetime',
        'imported_at' => 'datetime',
        'source_updated_at' => 'datetime',

        'raw_payload' => 'array',
    ];

    /* =====================
     * RELATIONS
     * ===================== */

    /** Store (stores.id) */
    public function store(): BelongsTo
    {
        return $this->belongsTo(Store::class, 'store_id');
    }

    public function marketplaceOrder(): BelongsTo
    {
        return $this->belongsTo(MarketplaceOrder::class, 'marketplace_order_id');
    }

    /** Marketplace items */
    public function items(): HasMany
    {
        return $this->hasMany(MpShipmentItem::class, 'mp_shipment_id');
    }

    /** Reconciliation to operasional shipment */
    public function reconciliation(): HasOne
    {
        return $this->hasOne(MpReconciliation::class, 'mp_shipment_id');
    }

    /* =====================
     * HELPERS
     * ===================== */

    /** Date used for reconcile (prefer shipped_at) */
    public function effectiveShipDate(): ?string
    {
        $dt = $this->shipped_at ?? $this->order_created_at;
        return $dt?->toDateString();
    }

    public function isReconciled(): bool
    {
        return $this->reconciliation !== null
        && !empty($this->reconciliation->matched_at);
    }

}
