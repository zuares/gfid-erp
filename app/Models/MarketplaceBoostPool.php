<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class MarketplaceBoostPool extends Model
{
    protected $table = 'marketplace_boost_pool';

    protected $fillable = [
        'store_id', 'marketplace_product_id', 'is_active', 'sort_order', 'last_boosted_at',
    ];

    protected $casts = [
        'is_active'       => 'boolean',
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
