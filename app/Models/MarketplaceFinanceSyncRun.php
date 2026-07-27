<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class MarketplaceFinanceSyncRun extends Model
{
    protected $table = 'marketplace_finance_sync_runs';

    protected $fillable = [
        'trigger',
        'status',
        'error_message',
        'output',
        'started_at',
        'finished_at',
    ];

    protected $casts = [
        'started_at'  => 'datetime',
        'finished_at' => 'datetime',
    ];
}
