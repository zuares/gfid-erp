<?php

namespace App\Models;

use App\Domain\Marketplace\Finance\Enums\ComponentDirection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class MarketplaceFinancialComponent extends Model
{
    protected $fillable = [
        'financial_transaction_id',
        'component_code',
        'component_name',
        'amount',
        'direction',
        'account_id',
        'provider_line_id',
        'source_hash',
        'raw_payload',
        'dedupe_key',
    ];

    protected $casts = [
        'amount' => 'decimal:2',
        'direction' => ComponentDirection::class,
        'raw_payload' => 'array',
    ];

    public function financialTransaction(): BelongsTo
    {
        return $this->belongsTo(MarketplaceFinancialTransaction::class, 'financial_transaction_id');
    }

    public function account(): BelongsTo
    {
        return $this->belongsTo(Account::class);
    }
}
