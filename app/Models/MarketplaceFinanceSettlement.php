<?php

namespace App\Models;

use App\Domain\Marketplace\Finance\Enums\SettlementStatus;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class MarketplaceFinanceSettlement extends Model
{
    protected $fillable = [
        'store_id',
        'channel',
        'external_settlement_id',
        'settlement_date',
        'amount',
        'currency',
        'bank_account_id',
        'status',
        'journal_id',
        'received_at',
        'raw_payload',
    ];

    protected $casts = [
        'settlement_date' => 'date',
        'amount' => 'decimal:2',
        'status' => SettlementStatus::class,
        'received_at' => 'datetime',
        'raw_payload' => 'array',
    ];

    public function store(): BelongsTo
    {
        return $this->belongsTo(Store::class);
    }

    public function bankAccount(): BelongsTo
    {
        return $this->belongsTo(Account::class, 'bank_account_id');
    }

    public function journal(): BelongsTo
    {
        return $this->belongsTo(Journal::class);
    }

    public function allocations(): HasMany
    {
        return $this->hasMany(MarketplaceFinanceSettlementAllocation::class, 'settlement_id');
    }
}
