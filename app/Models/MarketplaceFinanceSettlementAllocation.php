<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class MarketplaceFinanceSettlementAllocation extends Model
{
    protected $fillable = [
        'settlement_id',
        'financial_transaction_id',
        'order_sn',
        'allocated_amount',
    ];

    protected $casts = [
        'allocated_amount' => 'decimal:2',
    ];

    public function settlement(): BelongsTo
    {
        return $this->belongsTo(MarketplaceFinanceSettlement::class, 'settlement_id');
    }

    public function financialTransaction(): BelongsTo
    {
        return $this->belongsTo(MarketplaceFinancialTransaction::class, 'financial_transaction_id');
    }
}
