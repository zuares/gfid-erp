<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SewingReturn extends Model
{
    use HasFactory;

    protected $fillable = [
        'code',
        'date',
        'warehouse_id',
        'destination_warehouse_id', // ✅ NEW
        'operator_id',
        'status',
        'notes',
        'pickup_id',
        'qty_direct_picked',
        'voided_at',
        'voided_by_user_id',
        'void_reason',
    ];

    protected $casts = [
        'date' => 'date',
        'voided_at' => 'datetime',
    ];

    public function lines()
    {
        return $this->hasMany(SewingReturnLine::class);
    }

    public function operator()
    {
        return $this->belongsTo(Employee::class, 'operator_id');
    }

    public function qcResults()
    {
        return $this->hasMany(QcResult::class, 'sewing_job_id');
    }

    public function warehouse()
    {
        return $this->belongsTo(Warehouse::class, 'warehouse_id');
    }

    // ✅ NEW: gudang tujuan OK (WH-PRD / WH-RTS)
    public function destinationWarehouse()
    {
        return $this->belongsTo(Warehouse::class, 'destination_warehouse_id');
    }

    public function pickup()
    {
        return $this->belongsTo(SewingPickup::class, 'pickup_id');
    }

    public function directPickupLines()
    {
        return $this->hasMany(\App\Models\DirectPickupLine::class, 'sewing_return_id');
    }
}
