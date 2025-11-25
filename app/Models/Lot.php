<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Lot extends Model
{
    protected $fillable = [
        'code',
        'item_id',
        'initial_qty',
        'initial_cost',
        'qty_onhand',
        'total_cost',
        'avg_cost',
        'status',
    ];

    protected $casts = [
        'initial_qty' => 'decimal:3',
        'initial_cost' => 'decimal:2',
        'qty_onhand' => 'decimal:3',
        'total_cost' => 'decimal:2',
        'avg_cost' => 'decimal:4',
    ];

    public function item()
    {
        return $this->belongsTo(Item::class);
    }

    public function externalTransferLines()
    {
        return $this->hasMany(ExternalTransferLine::class);
    }

}
