<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class MarketplaceProductModel extends Model
{
    protected $fillable = [
        'marketplace_product_id', 'model_id', 'model_name', 'model_sku',
        'price', 'stock', 'raw_json',
    ];

    protected $casts = [
        'raw_json' => 'array',
        'price'    => 'decimal:2',
    ];

    public function product(): BelongsTo
    {
        return $this->belongsTo(MarketplaceProduct::class, 'marketplace_product_id');
    }
}
