<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class MpPacketItem extends Model
{
    protected $table = 'mp_packet_items';

    protected $fillable = [
        'channel',
        'store',
        'mp_shipment_id',
        'sku',
        'name',
        'qty',
        'item_id',
        'mapped_at',
        'mapped_by',
    ];

    protected $casts = [
        'qty' => 'integer',
        'mapped_at' => 'datetime',
    ];

    public function item(): BelongsTo
    {
        return $this->belongsTo(Item::class);
    }
}
