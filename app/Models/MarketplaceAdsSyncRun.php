<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class MarketplaceAdsSyncRun extends Model
{
    protected $table = 'marketplace_ads_sync_runs';

    protected $fillable = [
        'store_id',
        'sync_type',
        'date_from',
        'date_to',
        'status',
        'total_requests',
        'total_received',
        'total_inserted',
        'total_updated',
        'started_at',
        'finished_at',
        'error_message',
        'metadata',
    ];

    protected $casts = [
        'date_from' => 'date',
        'date_to' => 'date',
        'total_requests' => 'integer',
        'total_received' => 'integer',
        'total_inserted' => 'integer',
        'total_updated' => 'integer',
        'started_at' => 'datetime',
        'finished_at' => 'datetime',
        'metadata' => 'array',
    ];

    public function store(): BelongsTo
    {
        return $this->belongsTo(Store::class);
    }
}
