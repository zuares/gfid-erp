<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class MarketplaceVoucher extends Model
{
    protected $fillable = [
        'store_id', 'channel_code', 'voucher_id', 'voucher_code', 'voucher_name',
        'voucher_status', 'voucher_type', 'reward_type', 'usage_quantity', 'current_usage',
        'start_time', 'end_time', 'display_start_time', 'min_basket_price',
        'discount_amount', 'percentage', 'max_price', 'display_channel_list', 'item_id_list',
        'request_payload', 'raw_json', 'detail_cache_json', 'detail_cached_at',
        'sync_status', 'sync_error',
    ];

    protected $casts = [
        'store_id' => 'integer', 'voucher_id' => 'integer', 'voucher_type' => 'integer',
        'reward_type' => 'integer', 'usage_quantity' => 'integer', 'current_usage' => 'integer',
        'start_time' => 'integer', 'end_time' => 'integer', 'display_start_time' => 'integer',
        'min_basket_price' => 'float', 'discount_amount' => 'float', 'percentage' => 'float',
        'max_price' => 'float', 'display_channel_list' => 'array', 'item_id_list' => 'array',
        'request_payload' => 'array', 'raw_json' => 'array', 'detail_cache_json' => 'array',
        'detail_cached_at' => 'datetime',
    ];

    public function store(): BelongsTo
    {
        return $this->belongsTo(Store::class);
    }
}
