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
        'unit_cost',
        'wage_per_pcs',
        'void_reason',
        'voided_at',
        'voided_by',
        'qty_direct_picked',
        // ── penutupan administratif (WIP Cleanup) ──
        'qty_closed',
        'close_action',
        'closed_at',
        'closed_by',
    ];

    protected $casts = [
        'closed_at' => 'datetime',
    ];

    // 🔹 Header Sewing Pickup
    public function sewingPickup()
    {
        return $this->belongsTo(SewingPickup::class, 'sewing_pickup_id');
    }

    public function pickup()
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

    public function supplyLines()
    {
        return $this->hasMany(SewingPickupLineSupplyLine::class, 'sewing_pickup_line_id');
    }

    public function getSupplyStatusAttribute(): string
    {
        $lines = $this->relationLoaded('supplyLines') ? $this->supplyLines : $this->supplyLines()->get();

        if ($lines->isEmpty()) {
            return 'unmigrated';
        }

        $epsilon = 0.000001;
        $hasIssued = $lines->contains(fn($line) => (float) ($line->issued_qty ?? 0) > $epsilon);
        $isComplete = $lines->every(
            fn($line) => (float) ($line->issued_qty ?? 0) + $epsilon >= (float) ($line->required_qty ?? 0)
        );

        if ($isComplete) {
            return 'complete';
        }

        return $hasIssued ? 'partial' : 'incomplete';
    }

    public function progressAdjustments()
    {
        return $this->hasMany(SewingProgressAdjustmentLine::class);
    }

}
