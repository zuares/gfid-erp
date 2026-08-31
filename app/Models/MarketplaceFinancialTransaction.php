<?php

namespace App\Models;

use App\Domain\Marketplace\Finance\Enums\EscrowStatus;
use App\Domain\Marketplace\Finance\Enums\IncomeStatus;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class MarketplaceFinancialTransaction extends Model
{
    protected $fillable = [
        'store_id',
        'channel',
        'marketplace_order_id',
        'order_sn',
        'sales_invoice_id',
        'shipment_id',
        'currency',
        'gross_amount',
        'net_amount',
        'escrow_status',
        'income_status',
        'income_source_hash',
        'income_raw_payload',
        'income_synced_at',
        'released_at',
        'source_hash',
        'raw_payload',
        'synced_at',
        'sale_journal_id',
        'escrow_journal_id',
    ];

    protected $casts = [
        'gross_amount' => 'decimal:2',
        'net_amount' => 'decimal:2',
        'escrow_status' => EscrowStatus::class,
        'income_status' => IncomeStatus::class,
        'income_raw_payload' => 'array',
        'income_synced_at' => 'datetime',
        'released_at' => 'datetime',
        'raw_payload' => 'array',
        'synced_at' => 'datetime',
    ];

    public function store(): BelongsTo
    {
        return $this->belongsTo(Store::class);
    }

    public function marketplaceOrder(): BelongsTo
    {
        return $this->belongsTo(MarketplaceOrder::class);
    }

    public function salesInvoice(): BelongsTo
    {
        return $this->belongsTo(SalesInvoice::class);
    }

    public function shipment(): BelongsTo
    {
        return $this->belongsTo(Shipment::class);
    }

    public function components(): HasMany
    {
        return $this->hasMany(MarketplaceFinancialComponent::class, 'financial_transaction_id');
    }

    public function allocations(): HasMany
    {
        return $this->hasMany(MarketplaceFinanceSettlementAllocation::class, 'financial_transaction_id');
    }

    public function saleJournal(): BelongsTo
    {
        return $this->belongsTo(Journal::class, 'sale_journal_id');
    }

    public function escrowJournal(): BelongsTo
    {
        return $this->belongsTo(Journal::class, 'escrow_journal_id');
    }
}
