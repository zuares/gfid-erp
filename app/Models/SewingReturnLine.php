<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SewingReturnLine extends Model
{
    use HasFactory;

    protected $fillable = [
        'sewing_return_id',
        'sewing_pickup_line_id',
        'qty_ok',
        'qty_reject',
        'notes',
    ];

    protected $casts = [
        'qty_ok' => 'float',
        'qty_reject' => 'float',
    ];

    /*
    |--------------------------------------------------------------------------
    | RELASI
    |--------------------------------------------------------------------------
     */

    public function sewingReturn()
    {
        return $this->belongsTo(SewingReturn::class);
    }

    public function pickupLine()
    {
        return $this->belongsTo(SewingPickupLine::class, 'sewing_pickup_line_id');
    }

    // bundle → via pickupLine
    public function bundle()
    {
        return $this->hasOneThrough(
            CuttingJobBundle::class,
            SewingPickupLine::class,
            'id', // FK di sewing_pickup_line
            'id', // FK di cutting_job_bundle
            'sewing_pickup_line_id', // sewing_return_lines.sewing_pickup_line_id
            'cutting_job_bundle_id', // sewing_pickup_lines.cutting_job_bundle_id
            'ok_qty',
            'finished_qty',
        );
    }

    // item jadi → via bundle
    public function finishedItem()
    {
        return $this->bundle?->finishedItem();
    }

    public function sewingPickupLine()
    {
        // ⬅️ RELASI YANG DIBUTUHKAN BLADE
        return $this->belongsTo(SewingPickupLine::class, 'sewing_pickup_line_id');
    }
}
