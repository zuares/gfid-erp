<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class InventoryAuditFinding extends Model
{
    use HasFactory;

    protected $fillable = [
        'category',
        'severity',
        'title',
        'message',
        'item_id',
        'shipment_id',
        'fulfillment_id',
        'payload',
        'detected_at',
        'resolved_at',
    ];

    protected $casts = [
        'payload' => 'array',
        'detected_at' => 'datetime',
        'resolved_at' => 'datetime',
    ];
}
