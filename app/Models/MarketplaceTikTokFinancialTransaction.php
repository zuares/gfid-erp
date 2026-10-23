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
        'source',
        'transaction_type',
        'amount',
        'currency',
        'money_flow',
        'status',
        'transaction_created_at',
        'statement_id',
        'order_id',
        'adjustment_id',
        'settlement_amount',
        'revenue_amount',
        'shipping_cost_amount',
        'fee_tax_amount',
        'adjustment_amount',
        'reserve_amount',
        'source_payload',
        'synced_at',
    ];

    protected $casts = [
        'amount' => 'decimal:2',
        'settlement_amount' => 'decimal:2',
        'revenue_amount' => 'decimal:2',
        'shipping_cost_amount' => 'decimal:2',
        'fee_tax_amount' => 'decimal:2',
        'adjustment_amount' => 'decimal:2',
        'reserve_amount' => 'decimal:2',
        'transaction_created_at' => 'datetime',
        'source_payload' => 'array',
        'synced_at' => 'datetime',
    ];

    public function store(): BelongsTo
    {
        return $this->belongsTo(Store::class);
    }
}
