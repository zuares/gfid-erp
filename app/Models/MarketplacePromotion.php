<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class MarketplacePromotion extends Model
{
    protected $table = 'marketplace_promotions';

    protected $fillable = [
        'store_id',
        'channel_code',
        'discount_id',
        'source_discount_id',
        'discount_name',
        'discount_status',
        'sync_status',
        'sync_error',
        'start_time',
        'end_time',
        'item_count',
        'item_list_json',
        'request_payload',
        'create_response',
        'items_response',
        'update_response',
        'end_response',
        'delete_response',
        'detail_cache_json',
        'detail_cached_at',
        'raw_json',
        'synced_at',
        'ended_at',
    ];

    protected $casts = [
        'store_id' => 'integer',
        'discount_id' => 'integer',
        'source_discount_id' => 'integer',
        'start_time' => 'integer',
        'end_time' => 'integer',
        'item_count' => 'integer',
        'item_list_json' => 'array',
        'request_payload' => 'array',
        'create_response' => 'array',
        'items_response' => 'array',
        'update_response' => 'array',
        'end_response' => 'array',
        'delete_response' => 'array',
        'detail_cache_json' => 'array',
        'detail_cached_at' => 'datetime',
        'raw_json' => 'array',
        'synced_at' => 'datetime',
        'ended_at' => 'datetime',
    ];

    public function store(): BelongsTo
    {
        return $this->belongsTo(Store::class);
    }
}
