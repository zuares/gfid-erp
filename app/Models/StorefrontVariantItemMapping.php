<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class StorefrontVariantItemMapping extends Model
{
    protected $fillable = [
        'product_id',
        'variant_id',
        'size_id',
        'item_id',
        'price_override',
        'stock_override',
    ];

    protected $casts = [
        'price_override' => 'integer',
        'stock_override' => 'integer',
    ];

    public function product(): BelongsTo
    {
        return $this->belongsTo(StorefrontProduct::class, 'product_id');
    }

    public function variant(): BelongsTo
    {
        return $this->belongsTo(StorefrontProductVariant::class, 'variant_id');
    }

    public function size(): BelongsTo
    {
        return $this->belongsTo(StorefrontProductSize::class, 'size_id');
    }

    public function item(): BelongsTo
    {
        return $this->belongsTo(Item::class, 'item_id');
    }
}
