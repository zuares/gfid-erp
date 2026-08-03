<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ShipmentScanRingtone extends Model
{
    protected $fillable = [
        'name',
        'original_name',
        'path',
        'mime_type',
        'extension',
        'size_bytes',
        'compressed_size_bytes',
        'duration_ms',
        'uploaded_by',
    ];

    protected $casts = [
        'size_bytes' => 'integer',
        'compressed_size_bytes' => 'integer',
        'duration_ms' => 'integer',
    ];

    public function uploader(): BelongsTo
    {
        return $this->belongsTo(User::class, 'uploaded_by');
    }
}
