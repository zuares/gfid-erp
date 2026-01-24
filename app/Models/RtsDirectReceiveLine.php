<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class RtsDirectReceiveLine extends Model
{
    protected $fillable = [
        'rts_direct_receive_id', 'line_no',
        'item_id', 'qty', 'notes',
    ];

    public function header()
    {
        return $this->belongsTo(RtsDirectReceive::class, 'rts_direct_receive_id');
    }

    public function item()
    {
        return $this->belongsTo(Item::class, 'item_id');
    }
}
