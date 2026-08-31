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
        'paid_at',
        'shipped_at',
        'delivered_at',
        'synced_at',
        'processed_api_checked_at',
        'shipping_arranged_at',
        'raw_json',
        'settlement_sync_error_code',
        'settlement_sync_failed_at',
        'settlement_sync_last_attempt_at',
        'financial_data_status',
        'financial_issue_reason',
        'financial_checked_at',

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
        'delivery_failed',
        'delivery_failed_at',
        'tracking_status',
        'tracking_description',
        'tracking_checked_at',
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
        'meta',
    ];

    protected $casts = [
        'total_amount'  => 'decimal:2',
        'ordered_at'    => 'datetime',
        'paid_at'       => 'datetime',
        'shipped_at'    => 'datetime',
        'delivered_at'  => 'datetime',
        'synced_at'     => 'datetime',
        'processed_api_checked_at' => 'datetime',
        'shipping_arranged_at' => 'datetime',
        'settlement_sync_failed_at' => 'datetime',
        'settlement_sync_last_attempt_at' => 'datetime',
        'raw_json'      => 'array',
        'meta'          => 'array',
        'payment_date'  => 'datetime',
        'completed_at'  => 'datetime',
        'cancelled_at'  => 'datetime',
        'delivery_failed' => 'boolean',
        'delivery_failed_at' => 'datetime',
        'tracking_checked_at' => 'datetime',
        'order_date'    => 'datetime',
        'financial_checked_at' => 'datetime',
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

    public function incomeEstimate(): HasOne
    {
        return $this->hasOne(MarketplaceOrderIncomeEstimate::class, 'marketplace_order_id');
    }

    /**
     * Finance bounded submodule record. This is intentionally separate from
     * the legacy settlement/income relationships above.
     */
    public function financialTransactions(): HasMany
    {
        return $this->hasMany(MarketplaceFinancialTransaction::class, 'marketplace_order_id');
    }

    public function items(): HasMany
    {
        return $this->hasMany(MarketplaceOrderItem::class);
    }

    public function shipments(): HasMany
    {
        return $this->hasMany(MpShipment::class, 'marketplace_order_id');
    }

    public function getNeedsShippingArrangementAttribute(): bool
    {
        $logisticsStatus = $this->raw_json['package_list'][0]['logistics_status'] ?? null;
        $isArrangedViaApp = in_array($logisticsStatus, ['LOGISTICS_REQUEST_CREATED', 'LOGISTICS_READY_TO_SHIP', 'LOGISTICS_SHIPPED', 'LOGISTICS_READY']);

        return $this->order_status === 'READY_TO_SHIP' 
            && is_null($this->shipping_arranged_at)
            && empty($this->shipping_awb_no)
            && !$isArrangedViaApp;
    }
}
