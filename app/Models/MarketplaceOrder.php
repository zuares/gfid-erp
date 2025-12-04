<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class MarketplaceOrder extends Model
{
    protected $fillable = [
        'store_id',
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
        'order_date' => 'datetime',
        'payment_date' => 'datetime',
        'completed_at' => 'datetime',
        'cancelled_at' => 'datetime',
    ];

    public function store()
    {
        return $this->belongsTo(MarketplaceStore::class, 'store_id');
    }

    public function customer()
    {
        return $this->belongsTo(Customer::class, 'customer_id');
    }

    public function items()
    {
        return $this->hasMany(MarketplaceOrderItem::class, 'order_id');
    }

    public function getDisplayStatusAttribute(): string
    {
        return ucfirst($this->status);
    }
}
