<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ExternalTransferLine extends Model
{
    protected $fillable = [
        'external_transfer_id',
        'lot_id',
        'item_id',
        'item_code',
        'qty',
        'unit',
        'notes',
    ];

    protected $casts = [
        'qty' => 'decimal:3',
    ];

    public function transfer()
    {
        return $this->belongsTo(ExternalTransfer::class, 'external_transfer_id');
    }

    public function lot()
    {
        return $this->belongsTo(Lot::class);
    }

    public function item()
    {
        return $this->belongsTo(Item::class);
    }
}
