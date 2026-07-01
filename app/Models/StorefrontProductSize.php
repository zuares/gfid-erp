<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class StorefrontProductSize extends Model
{
    protected $fillable = [
        'product_id', 'size_label', 'price_override', 'sort_order', 'is_active',
    ];

    protected $casts = [
        'price_override' => 'integer',
        'is_active'      => 'boolean',
    ];

    public function product(): BelongsTo
    {
        return $this->belongsTo(StorefrontProduct::class, 'product_id');
    }
}
