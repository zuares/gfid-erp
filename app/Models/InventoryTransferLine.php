<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class InventoryTransferLine extends Model
{
    protected $fillable = [
        'inventory_transfer_id',
        'item_id',
        'qty',
        'notes',
    ];

    protected $casts = [
        'qty' => 'decimal:2',
    ];

    public function transfer()
    {
        return $this->belongsTo(InventoryTransfer::class, 'inventory_transfer_id');
    }

    public function item()
    {
        return $this->belongsTo(Item::class);
    }
}
