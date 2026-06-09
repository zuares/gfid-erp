<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class MarketplaceOrderSettlement extends Model
{
    protected $fillable = [
        'store_id',
        'order_id',
        'channel_order_id',

        // Pembayaran customer
        'buyer_payment_amount',

        // Fee marketplace
        'commission_fee',
        'service_fee',
        'transaction_fee',

        // Voucher & diskon seller
        'seller_voucher',
        'seller_coin_cash_back',

        // Ongkir
        'actual_shipping_fee',
        'shipping_fee_subsidy',
        'reverse_shipping_fee',

        // Campaign & lainnya
        'activity_fee',
        'drc_adjustable_refund',
        'escrow_tax',

        // Biaya iklan (manual / import)
        'ad_cost',

        // Dana cair
        'final_income',
        'settlement_time',

        // Meta
        'synced_at',
        'raw_json',
    ];

    protected $casts = [
        'buyer_payment_amount'   => 'decimal:2',
        'commission_fee'         => 'decimal:2',
        'service_fee'            => 'decimal:2',
        'transaction_fee'        => 'decimal:2',
        'seller_voucher'         => 'decimal:2',
        'seller_coin_cash_back'  => 'decimal:2',
        'actual_shipping_fee'    => 'decimal:2',
        'shipping_fee_subsidy'   => 'decimal:2',
        'reverse_shipping_fee'   => 'decimal:2',
        'activity_fee'           => 'decimal:2',
        'drc_adjustable_refund'  => 'decimal:2',
        'escrow_tax'             => 'decimal:2',
        'ad_cost'                => 'decimal:2',
        'final_income'           => 'decimal:2',
        'settlement_time'        => 'datetime',
        'synced_at'              => 'datetime',
        'raw_json'               => 'array',
    ];

    public function store(): BelongsTo
    {
        return $this->belongsTo(Store::class);
    }

    public function order(): BelongsTo
    {
        return $this->belongsTo(MarketplaceOrder::class, 'order_id');
    }

    /** Total semua potongan. */
    public function totalDeductions(): float
    {
        return (float) $this->commission_fee
            + (float) $this->service_fee
            + (float) $this->transaction_fee
            + (float) $this->seller_voucher
            + (float) $this->activity_fee
            + (float) $this->drc_adjustable_refund
            + (float) $this->escrow_tax;
    }
}
