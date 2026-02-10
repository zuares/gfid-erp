<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class MpIncome extends Model
{
    protected $table = 'mp_incomes';

    protected $fillable = [
        'store_id',
        'channel',
        'platform_order_id',

        'released_at',
        'released_date',

        'platform_fee_total',
        'refund_total',
        'net_payout_actual',

        'currency',
        'source_file',
        'import_batch_id',

        'raw_payload',
    ];

    protected $casts = [
        'store_id' => 'integer',

        'released_at' => 'datetime',
        'released_date' => 'date',

        // decimal columns: Laravel will return strings (presisi aman)
        'platform_fee_total' => 'decimal:2',
        'refund_total' => 'decimal:2',
        'net_payout_actual' => 'decimal:2',

        // SQLite json/text -> array
        'raw_payload' => 'array',
    ];

    /*
     * Indexes (informational):
     * - UNIQUE(store_id, channel, platform_order_id) => anti double (updateOrCreate aman)
     * - INDEX(channel, platform_order_id)           => lookup cepat by order
     * - INDEX(released_at)                          => timeline/debug
     * - INDEX(store_id, channel, released_date)     => report/recon payout WIB (recommended)
     * - INDEX(import_batch_id)                      => audit import per batch
     */

    public function store(): BelongsTo
    {
        return $this->belongsTo(Store::class);
    }

    public function shipment()
    {
        return $this->belongsTo(\App\Models\MpShipment::class, 'platform_order_id', 'platform_order_id')
            ->whereColumn('mp_shipments.store_id', 'mp_incomes.store_id')
            ->whereColumn('mp_shipments.channel', 'mp_incomes.channel');
    }
}
