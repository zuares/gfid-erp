<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class MpIncome extends Model
{
    protected $fillable = [
        'store_id',
        'channel',
        'platform_order_id',
        'released_at',
        'platform_fee_total',
        'refund_total',
        'net_payout_actual',
        'currency',
        'source_file',
        'import_batch_id',
        'raw_payload',
    ];

    protected $casts = [
        'released_at' => 'datetime',
        'raw_payload' => 'array',
    ];

    public function store()
    {
        return $this->belongsTo(Store::class);
    }
}
