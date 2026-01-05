<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SewingProgressAdjustment extends Model
{
    protected $fillable = [
        'code',
        'date',
        'sewing_pickup_id',
        'operator_id',
        'notes',
        'created_by',
    ];

    public function pickup()
    {
        return $this->belongsTo(SewingPickup::class, 'sewing_pickup_id');
    }

    public function operator()
    {
        return $this->belongsTo(Employee::class, 'operator_id');
    }

    public function lines()
    {
        return $this->hasMany(SewingProgressAdjustmentLine::class, 'sewing_progress_adjustment_id');
    }
}
