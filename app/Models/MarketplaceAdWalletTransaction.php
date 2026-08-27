<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class MarketplaceAdWalletTransaction extends Model
{
    // SPM_DEDUCT is a transfer from marketplace clearing to the separate
    // prepaid ad wallet; 450/451 are usage/refund movements.
    protected $table = 'marketplace_ad_wallet_transactions';

    protected $fillable = [
        'store_id',
        'external_transaction_id',
        'transaction_type',
        'amount',
        'money_flow',
        'wallet_type',
        'order_sn',
        'status',
        'reason',
        'transaction_created_at',
        'source_payload',
    ];

    protected $casts = [
        'amount' => 'decimal:2',
        'transaction_created_at' => 'datetime',
        'source_payload' => 'array',
    ];

    public function store(): BelongsTo
    {
        return $this->belongsTo(Store::class);
    }
}
