<?php

namespace App\Models;

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
        'qty_issued',
        'notes',
        'qty_received_rts',
    ];

    protected $casts = [
        'qty_request' => 'decimal:2',
        'stock_snapshot_at_request' => 'decimal:2',
        'qty_issued' => 'decimal:2',
    ];

    /*
    |--------------------------------------------------------------------------
    |  RELATIONSHIPS
    |--------------------------------------------------------------------------
     */

    /**
     * Header stock request.
     */
    public function request()
    {
        return $this->belongsTo(StockRequest::class, 'stock_request_id');
    }

    /**
     * Item yang diminta.
     */
    public function item()
    {
        return $this->belongsTo(Item::class);
    }

    /*
    |--------------------------------------------------------------------------
    |  HELPERS
    |--------------------------------------------------------------------------
     */

    /**
     * Apakah line ini sudah di-issue (qty_issued ada dan > 0).
     */
    public function isIssued(): bool
    {
        return !is_null($this->qty_issued) && (float) $this->qty_issued > 0;
    }

    /**
     * Apakah qty_issued sama dengan qty_request (terpenuhi penuh).
     */
    public function isFullyIssued(): bool
    {
        $requested = (float) $this->qty_request;
        $issued = (float) ($this->qty_issued ?? 0);

        return $requested > 0 && bccomp((string) $requested, (string) $issued, 2) === 0;
    }

    /**
     * Apakah hanya terpenuhi sebagian.
     */
    public function isPartiallyIssued(): bool
    {
        $requested = (float) $this->qty_request;
        $issued = (float) ($this->qty_issued ?? 0);

        return $issued > 0 && $issued < $requested;
    }
}
