<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Fakta harian per campaign (grain terkecil dari Shopee Ads API).
 * Rasio dihitung saat agregasi, bukan disimpan di sini.
 */
class MarketplaceAdCampaignDaily extends Model
{
    protected $table = 'marketplace_ad_campaign_dailies';

    protected $fillable = [
        'store_id',
        'channel_campaign_id',
        'date',
        'ad_type',
        'impressions',
        'clicks',
        'expense',
        'broad_order',
        'broad_order_amount',
        'broad_gmv',
        'direct_order',
        'direct_order_amount',
        'direct_gmv',
        'cpc',
        'raw_json',
    ];

    protected $casts = [
        'date'         => 'date:Y-m-d',
        'impressions'  => 'integer',
        'clicks'       => 'integer',
        'expense'      => 'decimal:2',
        'broad_order'  => 'integer',
        'broad_gmv'    => 'decimal:2',
        'direct_order' => 'integer',
        'direct_gmv'   => 'decimal:2',
        'cpc'          => 'decimal:4',
        'raw_json'     => 'array',
    ];

    public function store(): BelongsTo
    {
        return $this->belongsTo(Store::class);
    }

    public function campaign(): BelongsTo
    {
        return $this->belongsTo(MarketplaceAdCampaign::class, 'channel_campaign_id', 'channel_campaign_id');
    }
}
