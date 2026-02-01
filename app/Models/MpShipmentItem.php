<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class MpShipmentItem extends Model
{
    protected $table = 'mp_shipment_items';

    protected $fillable = [
        'mp_shipment_id',
        'sku_code',
        'sku_parent',
        'product_name',
        'variant_name',
        'qty',
        'unit_price',
        'subtotal',
        'raw_line',
    ];

    protected $casts = [
        'mp_shipment_id' => 'integer',
        'qty' => 'integer',
        'unit_price' => 'decimal:2',
        'subtotal' => 'decimal:2',
        'raw_line' => 'array',
    ];

    /* =====================
     * RELATIONS
     * ===================== */

    public function mpShipment(): BelongsTo
    {
        return $this->belongsTo(MpShipment::class, 'mp_shipment_id');
    }
}
