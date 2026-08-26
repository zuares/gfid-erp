<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class MarketplaceOrderIncomeEstimate extends Model
{
    protected $fillable = [
        'store_id',
        'marketplace_order_id',
        'channel_order_id',
        'income_status',
        'estimated_escrow_amount',
        'estimated_payout_at',
        'payment_method',
        'status_description',
        'currency',
        'source_created_at',
        'synced_at',
        'raw_json',
    ];

    protected $casts = [
        'income_status' => 'integer',
        'estimated_escrow_amount' => 'decimal:2',
        'estimated_payout_at' => 'datetime',
        'source_created_at' => 'datetime',
        'synced_at' => 'datetime',
        'raw_json' => 'array',
    ];

    public function store(): BelongsTo
    {
        return $this->belongsTo(Store::class);
    }

    public function order(): BelongsTo
    {
        return $this->belongsTo(MarketplaceOrder::class, 'marketplace_order_id');
    }
}
