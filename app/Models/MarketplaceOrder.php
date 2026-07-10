<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class MarketplaceOrder extends Model
{
    protected $fillable = [
        // Omnichannel (primary)
        'store_id',
        'channel_order_id',
        'booking_sn',
        'order_status',
        'buyer_username',
        'payment_method',
        'shipping_carrier',
        'total_amount',
        'currency',
        'ordered_at',
        'synced_at',
        'shipping_arranged_at',
        'raw_json',

        // Legacy — digunakan modul marketplace lama
        'external_order_id',
        'external_invoice_no',
        'order_date',
        'status',
        'buyer_name',
        'buyer_phone',
        'shipping_address',
        'shipping_city',
        'shipping_province',
        'shipping_postal_code',
        'shipping_courier_code',
        'shipping_awb_no',
        'subtotal_items',
        'shipping_fee_customer',
        'shipping_discount_platform',
        'voucher_discount',
        'other_discount',
        'total_paid_customer',
        'platform_fee_total',
        'net_payout_estimated',
        'payment_status',
        'payment_date',
        'completed_at',
        'cancelled_at',
        'customer_id',
        'remarks',
        'raw_payload_json',
    ];

    protected $casts = [
        'total_amount'  => 'decimal:2',
        'ordered_at'    => 'datetime',
        'synced_at'     => 'datetime',
        'shipping_arranged_at' => 'datetime',
        'raw_json'      => 'array',
        'payment_date'  => 'datetime',
        'completed_at'  => 'datetime',
        'cancelled_at'  => 'datetime',
        'order_date'    => 'datetime',
    ];

    public function store(): BelongsTo
    {
        return $this->belongsTo(Store::class);
    }

    public function fulfillment(): HasOne
    {
        return $this->hasOne(OrderFulfillment::class, 'marketplace_order_id');
    }

    public function settlement(): HasOne
    {
        return $this->hasOne(MarketplaceOrderSettlement::class, 'order_id');
    }

    public function items(): HasMany
    {
        return $this->hasMany(MarketplaceOrderItem::class);
    }

    public function getNeedsShippingArrangementAttribute(): bool
    {
        return $this->order_status === 'READY_TO_SHIP' && is_null($this->shipping_arranged_at);
    }
}
