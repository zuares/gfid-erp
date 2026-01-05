<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SewingProgressAdjustmentLine extends Model
{
    protected $fillable = [
        'sewing_progress_adjustment_id',
        'sewing_pickup_line_id',
        'qty_adjust',
        'reason',
    ];

    public function doc()
    {
        return $this->belongsTo(SewingProgressAdjustment::class, 'sewing_progress_adjustment_id');
    }

    public function sewingPickupLine()
    {
        return $this->belongsTo(SewingPickupLine::class, 'sewing_pickup_line_id');
    }
}
