<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class MarketplaceVoucherAutomation extends Model
{
    protected $fillable = [
        'store_id', 'name_prefix', 'code_prefix', 'interval_hours', 'duration_hours',
        'usage_quantity', 'voucher_type', 'reward_type', 'min_basket_price',
        'discount_amount', 'percentage', 'max_price', 'display_channel_list', 'item_id_list',
        'next_run_at', 'last_run_at', 'last_voucher_id', 'last_slot_key', 'run_count',
        'is_active', 'last_error',
    ];

    protected $casts = [
        'store_id' => 'integer', 'interval_hours' => 'integer', 'duration_hours' => 'integer',
        'usage_quantity' => 'integer', 'voucher_type' => 'integer', 'reward_type' => 'integer',
        'min_basket_price' => 'float', 'discount_amount' => 'float', 'percentage' => 'float',
        'max_price' => 'float', 'display_channel_list' => 'array', 'item_id_list' => 'array',
        'next_run_at' => 'integer', 'last_run_at' => 'integer', 'last_voucher_id' => 'integer',
        'run_count' => 'integer', 'is_active' => 'boolean',
    ];

    public function store(): BelongsTo
    {
        return $this->belongsTo(Store::class);
    }
}
