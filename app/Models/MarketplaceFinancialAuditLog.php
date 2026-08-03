<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class MarketplaceFinancialAuditLog extends Model
{
    protected $fillable = [
        'action',
        'scope_key',
        'store_id',
        'date_basis',
        'date_from',
        'date_to',
        'posting_id',
        'closing_id',
        'user_id',
        'before_snapshot',
        'after_snapshot',
        'reason',
    ];

    protected $casts = [
        'date_from' => 'date',
        'date_to' => 'date',
        'before_snapshot' => 'array',
        'after_snapshot' => 'array',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
