<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class MarketplaceAdsDaily extends Model
{
    protected $fillable = [
        'store_id', 'date', 'impressions', 'clicks', 'ctr',
        'spend', 'orders', 'gmv', 'roas', 'raw_json',
    ];

    protected $casts = [
        'raw_json' => 'array',
    ];

    public function store(): BelongsTo
    {
        return $this->belongsTo(Store::class);
    }
}
