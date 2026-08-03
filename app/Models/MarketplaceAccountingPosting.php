<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class MarketplaceAccountingPosting extends Model
{
    protected $fillable = [
        'store_id',
        'date_basis',
        'date_from',
        'date_to',
        'scope_key',
        'status',
        'journal_id',
        'order_count',
        'gross_sales',
        'payout',
        'posted_amount',
        'snapshot',
        'created_by',
        'posted_by',
        'posted_at',
        'voided_at',
        'void_reason',
    ];

    protected $casts = [
        'date_from' => 'date',
        'date_to' => 'date',
        'gross_sales' => 'decimal:2',
        'payout' => 'decimal:2',
        'posted_amount' => 'decimal:2',
        'snapshot' => 'array',
        'posted_at' => 'datetime',
        'voided_at' => 'datetime',
    ];

    public function journal(): BelongsTo
    {
        return $this->belongsTo(Journal::class);
    }

    public function store(): BelongsTo
    {
        return $this->belongsTo(Store::class);
    }
}
