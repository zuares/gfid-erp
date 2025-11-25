<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CuttingJob extends Model
{
    protected $fillable = [
        'code',
        'date',
        'warehouse_id',
        'lot_id',
        'fabric_item_id',
        'operator_id',
        'total_bundles',
        'total_qty_pcs',
        'status',
        'notes',
        'created_by',
        'updated_by',
    ];

    protected $casts = [
        'date' => 'date',
        'total_qty_pcs' => 'decimal:2',
    ];

    public function warehouse()
    {
        return $this->belongsTo(Warehouse::class);
    }

    public function lot()
    {
        return $this->belongsTo(Lot::class);
    }

    public function fabricItem()
    {
        return $this->belongsTo(Item::class, 'fabric_item_id');
    }

    public function operator()
    {
        return $this->belongsTo(Employee::class, 'operator_id');
    }

    public function bundles()
    {
        return $this->hasMany(CuttingJobBundle::class);
    }
}
