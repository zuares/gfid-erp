<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class MarketplaceAdsBalanceLog extends Model
{
    protected $fillable = ['store_id', 'balance'];

    public function store(): BelongsTo
    {
        return $this->belongsTo(Store::class);
    }
}
