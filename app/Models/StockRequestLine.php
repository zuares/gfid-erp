<?php

namespace App\Models;

use App\Models\Item;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class StockRequestLine extends Model
{
    use HasFactory;

    protected $fillable = [
        'stock_request_id',
        'line_no',
        'item_id',
        'qty_request',
        'stock_snapshot_at_request',
        'qty_dispatched',
        'qty_received',
        'qty_picked',
        'notes',
    ];

    protected $casts = [
        'qty_request' => 'decimal:3',
        'stock_snapshot_at_request' => 'decimal:3',
        'qty_dispatched' => 'decimal:3',
        'qty_received' => 'decimal:3',
        'qty_picked' => 'decimal:3',
    ];

    public function request()
    {
        return $this->belongsTo(StockRequest::class, 'stock_request_id');
    }

    public function item()
    {
        return $this->belongsTo(Item::class);
    }

    public function outstandingQty(): float
    {
        $req = (float) ($this->qty_request ?? 0);
        $rec = (float) ($this->qty_received ?? 0);
        $pick = (float) ($this->qty_picked ?? 0);
        return max($req - $rec - $pick, 0);
    }

    public function fulfilledQty(): float
    {
        return (float) ($this->qty_received ?? 0) + (float) ($this->qty_picked ?? 0);
    }
}
