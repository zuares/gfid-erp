<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class MpReconciliation extends Model
{
    protected $table = 'mp_reconciliations';

    protected $fillable = [
        'mp_shipment_id',
        'shipment_id',
        'match_key',
        'match_confidence',
        'matched_by',
        'matched_at',
        'notes',
    ];

    protected $casts = [
        'mp_shipment_id' => 'integer',
        'shipment_id' => 'integer',
        'match_confidence' => 'integer',
        'matched_by' => 'integer',
        'matched_at' => 'datetime',
    ];

    public function mpShipment(): BelongsTo
    {
        return $this->belongsTo(MpShipment::class, 'mp_shipment_id');
    }

    public function shipment(): BelongsTo
    {
        return $this->belongsTo(Shipment::class, 'shipment_id');
    }
}
