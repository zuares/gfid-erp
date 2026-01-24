<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class RtsDirectReceive extends Model
{
    protected $fillable = [
        'date', 'code',
        'from_warehouse_id', 'to_warehouse_id',
        'operator_id', 'notes',
        'created_by_user_id',
    ];

    public function lines(): HasMany
    {
        return $this->hasMany(RtsDirectReceiveLine::class, 'rts_direct_receive_id')
            ->orderBy('line_no');
    }

    public function fromWarehouse()
    {
        return $this->belongsTo(Warehouse::class, 'from_warehouse_id');
    }

    public function toWarehouse()
    {
        return $this->belongsTo(Warehouse::class, 'to_warehouse_id');
    }

    public function operator()
    {
        return $this->belongsTo(Employee::class, 'operator_id');
    }
}
