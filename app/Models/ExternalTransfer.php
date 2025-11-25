<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ExternalTransfer extends Model
{
    protected $fillable = [
        'code',
        'date',
        'process',
        'operator_code',
        'from_warehouse_id',
        'to_warehouse_id',
        'to_warehouse_code',
        'status',
        'notes',
        'created_by',
    ];

    protected $casts = [
        'date' => 'date',
    ];

    public function fromWarehouse()
    {
        return $this->belongsTo(Warehouse::class, 'from_warehouse_id');
    }

    public function toWarehouse()
    {
        return $this->belongsTo(Warehouse::class, 'to_warehouse_id');
    }

    public function creator()
    {
        // 🧷 ini yang dipakai di index & show
        return $this->belongsTo(User::class, 'created_by');
    }

    public function lines()
    {
        return $this->hasMany(ExternalTransferLine::class);
    }
}
