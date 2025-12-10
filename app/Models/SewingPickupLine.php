<?php
namespace App\Models;

use App\Models\CuttingJobBundle;
use App\Models\SewingPickup;
use Illuminate\Database\Eloquent\Model;

class SewingPickupLine extends Model
{
    protected $fillable = [
        'sewing_pickup_id',
        'cutting_job_bundle_id',
        'finished_item_id',
        'operator_id',
        'qty_bundle',
        'qty_returned_ok',
        'qty_returned_reject',
        'status',
        'notes',
    ];

    // 🔹 Header Sewing Pickup
    public function sewingPickup()
    {
        return $this->belongsTo(SewingPickup::class, 'sewing_pickup_id');
    }

    public function bundle()
    {
        return $this->belongsTo(CuttingJobBundle::class, 'cutting_job_bundle_id');
    }

    public function finishedItem()
    {
        return $this->belongsTo(Item::class, 'finished_item_id');
    }

    public function operator()
    {
        return $this->belongsTo(Employee::class, 'operator_id');
    }

    public function getReturnedQtyAttribute()
    {
        return (float) ($this->sewingReturnLines()->sum('qty_ok') +
            $this->sewingReturnLines()->sum('qty_reject'));
    }

    public function getStatusLabelAttribute()
    {
        $picked = (float) $this->qty_bundle;
        $returned = (float) $this->returned_qty;

        if ($returned <= 0) {
            return 'belum';
        }

        if ($returned < $picked) {
            return 'parsial';
        }

        return 'penuh'; // sudah disetor penuh
    }

    public function sewingReturnLines()
    {
        return $this->hasMany(\App\Models\SewingReturnLine::class, 'sewing_pickup_line_id');
    }

}
