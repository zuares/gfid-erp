<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class MarketplaceProductChangeLog extends Model
{
    protected $fillable = [
        'marketplace_product_id',
        'action',
        'status',
        'before_json',
        'after_json',
        'response_json',
        'error_message',
        'created_by',
    ];

    protected $casts = [
        'before_json' => 'array',
        'after_json' => 'array',
        'response_json' => 'array',
    ];

    public function product(): BelongsTo
    {
        return $this->belongsTo(MarketplaceProduct::class, 'marketplace_product_id');
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}
