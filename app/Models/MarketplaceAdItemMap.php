<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Override manual mapping produk iklan → item internal.
 */
class MarketplaceAdItemMap extends Model
{
    protected $table = 'marketplace_ad_item_maps';

    protected $fillable = [
        'store_id',
        'channel_code',
        'channel_item_id',
        'channel_campaign_id',
        'internal_item_id',
        'note',
        'created_by',
    ];

    protected $casts = [
        'channel_item_id'  => 'integer',
        'internal_item_id' => 'integer',
    ];

    public function item(): BelongsTo
    {
        return $this->belongsTo(Item::class, 'internal_item_id');
    }

    public function store(): BelongsTo
    {
        return $this->belongsTo(Store::class);
    }
}
