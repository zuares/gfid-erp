<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class MarketplaceProductImage extends Model
{
    protected $fillable = [
        'marketplace_product_id',
        'shopee_image_id',
        'image_url',
        'sort_order',
        'is_cover',
        'status',
        'raw_json',
    ];

    protected $casts = [
        'is_cover' => 'boolean',
        'raw_json' => 'array',
    ];

    public function product(): BelongsTo
    {
        return $this->belongsTo(MarketplaceProduct::class, 'marketplace_product_id');
    }
}
