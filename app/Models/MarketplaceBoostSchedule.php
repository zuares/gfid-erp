<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class MarketplaceBoostSchedule extends Model
{
    protected $fillable = [
        'store_id', 'marketplace_product_id', 'boost_time', 'priority',
        'is_active', 'last_fired_on', 'last_boosted_at',
    ];

    protected $casts = [
        'is_active'       => 'boolean',
        'last_fired_on'   => 'date',
        'last_boosted_at' => 'datetime',
    ];

    public function product(): BelongsTo
    {
        return $this->belongsTo(MarketplaceProduct::class, 'marketplace_product_id');
    }

    public function store(): BelongsTo
    {
        return $this->belongsTo(Store::class);
    }
}
