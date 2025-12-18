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
        'operator_id',
        'status',
        'notes',
        'pickup_id',
        'qty_direct_picked',
    ];

    protected $casts = [
        'date' => 'date',
    ];

    public function lines()
    {
        return $this->hasMany(SewingReturnLine::class);
    }

    public function operator()
    {
        return $this->belongsTo(Employee::class, 'operator_id');
    }

    public function warehouse()
    {
        return $this->belongsTo(Warehouse::class, 'warehouse_id');
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
