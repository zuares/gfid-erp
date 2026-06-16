<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PurchaseRequestLine extends Model
{
    protected $fillable = [
        'purchase_request_id',
        'item_id',
        'qty',
        'unit_price',
        'notes',
    ];

    protected $casts = [
        'qty'        => 'float',
        'unit_price' => 'float',
    ];

    // =========================================================
    // RELATIONSHIPS
    // =========================================================

    public function purchaseRequest(): BelongsTo
    {
        return $this->belongsTo(PurchaseRequest::class, 'purchase_request_id');
    }

    public function item(): BelongsTo
    {
        return $this->belongsTo(Item::class);
    }
}
