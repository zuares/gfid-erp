<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class MarketplaceFinancialClosing extends Model
{
    protected $fillable = [
        'store_id',
        'date_basis',
        'date_from',
        'date_to',
        'scope_key',
        'status',
        'snapshot',
        'closed_by',
        'closed_at',
        'reopened_by',
        'reopened_at',
        'reopen_reason',
    ];

    protected $casts = [
        'date_from' => 'date',
        'date_to' => 'date',
        'snapshot' => 'array',
        'closed_at' => 'datetime',
        'reopened_at' => 'datetime',
    ];

    public function store(): BelongsTo
    {
        return $this->belongsTo(Store::class);
    }

    public function auditLogs(): HasMany
    {
        return $this->hasMany(MarketplaceFinancialAuditLog::class, 'closing_id');
    }
}
