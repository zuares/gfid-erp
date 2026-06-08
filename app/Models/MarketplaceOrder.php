<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class MarketplaceOrder extends Model
{
    protected $fillable = [
        'raw_payload_json',
        'remarks',
        'customer_id',
        'cancelled_at',
        'completed_at',
        'payment_date',
        'payment_status',
        'net_payout_estimated',
        'platform_fee_total',
        'total_paid_customer',
        'other_discount',
        'voucher_discount',
        'shipping_discount_platform',
        'shipping_fee_customer',
        'subtotal_items',
        'shipping_awb_no',
        'shipping_courier_code',
        'shipping_postal_code',
        'shipping_province',
        'shipping_city',
        'shipping_address',
        'buyer_phone',
        'buyer_name',
        'status',
        'order_date',
        'external_invoice_no',
        'store_id',
        'channel_order_id',
        'external_order_id',
        'booking_sn',
        'order_status',
        'buyer_username',
        'payment_method',
        'shipping_carrier',
        'total_amount',
        'currency',
        'ordered_at',
        'synced_at',
        'raw_json',
    ];

    protected $casts = [
        'total_amount' => 'decimal:2',
        'ordered_at' => 'datetime',
        'synced_at' => 'datetime',
        'raw_json' => 'array',
    ];

    public function store(): BelongsTo
    {
        return $this->belongsTo(Store::class);
    }

    public function items(): HasMany
    {
        return $this->hasMany(MarketplaceOrderItem::class);
    }
}
