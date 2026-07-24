<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class MarketplaceAdsHourlyPerformance extends Model
{
    protected $table = 'marketplace_ads_hourly_performances';

    protected $fillable = [
        'store_id',
        'campaign_id',
        'channel_campaign_id',
        'performance_date',
        'performance_hour',
        'impression',
        'clicks',
        'ctr',
        'expense',
        'broad_gmv',
        'broad_order',
        'broad_order_amount',
        'broad_roi',
        'broad_cir',
        'conversion_rate',
        'cpc',
        'direct_order',
        'direct_order_amount',
        'direct_gmv',
        'direct_roi',
        'direct_cir',
        'direct_conversion_rate',
        'cost_per_direct_conversion',
        'raw_response',
        'synced_at',
    ];

    protected $casts = [
        'performance_date' => 'date',
        'performance_hour' => 'integer',
        'impression' => 'integer',
        'clicks' => 'integer',
        'ctr' => 'decimal:6',
        'expense' => 'decimal:2',
        'broad_gmv' => 'decimal:2',
        'broad_order' => 'integer',
        'broad_order_amount' => 'decimal:2',
        'broad_roi' => 'decimal:4',
        'broad_cir' => 'decimal:4',
        'conversion_rate' => 'decimal:6',
        'cpc' => 'decimal:4',
        'direct_order' => 'integer',
        'direct_order_amount' => 'decimal:2',
        'direct_gmv' => 'decimal:2',
        'direct_roi' => 'decimal:4',
        'direct_cir' => 'decimal:4',
        'direct_conversion_rate' => 'decimal:6',
        'cost_per_direct_conversion' => 'decimal:4',
        'raw_response' => 'array',
        'synced_at' => 'datetime',
    ];

    public function store(): BelongsTo
    {
        return $this->belongsTo(Store::class);
    }

    public function campaign(): BelongsTo
    {
        return $this->belongsTo(MarketplaceAdCampaign::class, 'campaign_id');
    }
}
