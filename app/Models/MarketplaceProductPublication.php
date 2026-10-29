<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class MarketplaceProductPublication extends Model
{
    protected $fillable = [
        'store_id',
        'channel_code',
        'idempotency_key',
        'status',
        'request_hash',
        'payload_json',
        'response_json',
        'error_message',
        'platform_product_id',
        'platform_request_id',
        'created_by',
        'completed_at',
    ];

    protected $casts = [
        'payload_json' => 'array',
        'response_json' => 'array',
        'completed_at' => 'datetime',
    ];

    public function store(): BelongsTo
    {
        return $this->belongsTo(Store::class);
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}
