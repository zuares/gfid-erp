<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class MarketplaceAdsItemDaily extends Model
{
    use HasFactory;

    protected $fillable = [
        'store_id',
        'channel_campaign_id',
        'channel_item_id',
        'date',
        'impressions',
        'clicks',
        'expense',
        'broad_order',
        'broad_gmv',
        'direct_order',
        'direct_gmv',
        'cpc',
        'raw_json',
    ];

    protected $casts = [
        'date' => 'date:Y-m-d',
        'impressions' => 'integer',
        'clicks' => 'integer',
        'expense' => 'decimal:2',
        'broad_order' => 'integer',
        'broad_gmv' => 'decimal:2',
        'direct_order' => 'integer',
        'direct_gmv' => 'decimal:2',
        'cpc' => 'decimal:4',
        'raw_json' => 'array',
    ];

    public function store()
    {
        return $this->belongsTo(Store::class);
    }
}
