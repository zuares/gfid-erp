<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class MarketplaceAdsCampaignItem extends Model
{
    protected $table = 'marketplace_ads_campaign_items';

    protected $fillable = [
        'campaign_id',
        'channel_item_id',
        'product_name',
        'status',
    ];

    public function campaign(): BelongsTo
    {
        return $this->belongsTo(MarketplaceAdCampaign::class, 'campaign_id');
    }
}
