<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class MarketplaceTikTokFinancialTransaction extends Model
{
    protected $table = 'marketplace_tiktok_financial_transactions';

    protected $fillable = [
        'store_id',
        'external_transaction_id',
        'transaction_type',
        'amount',
        'currency',
        'money_flow',
        'status',
        'transaction_created_at',
        'source_payload',
        'synced_at',
    ];

    protected $casts = [
        'amount' => 'decimal:2',
        'transaction_created_at' => 'datetime',
        'source_payload' => 'array',
        'synced_at' => 'datetime',
    ];

    public function store(): BelongsTo
    {
        return $this->belongsTo(Store::class);
    }
}
