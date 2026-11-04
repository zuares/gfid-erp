<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class MarketplaceShopeeWalletTransaction extends Model
{
    protected $table = 'marketplace_shopee_wallet_transactions';

    protected $fillable = [
        'store_id',
        'external_transaction_id',
        'transaction_type',
        'amount',
        'currency',
        'money_flow',
        'wallet_type',
        'current_balance',
        'order_sn',
        'buyer_name',
        'status',
        'reason',
        'transaction_created_at',
        'source_payload',
        'synced_at',
    ];

    protected $casts = [
        'amount' => 'decimal:2',
        'current_balance' => 'decimal:2',
        'transaction_created_at' => 'datetime',
        'source_payload' => 'array',
        'synced_at' => 'datetime',
    ];

    public function store(): BelongsTo
    {
        return $this->belongsTo(Store::class);
    }
}
