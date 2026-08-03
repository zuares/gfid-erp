<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class MarketplaceImportBatch extends Model
{
    protected $table = 'marketplace_import_batches';

    public $incrementing = false;
    protected $keyType = 'string';

    protected $fillable = [
        'id', 'channel', 'store_id', 'source_type', 'source_file', 'file_hash',
        'status', 'total_rows', 'shipments_parsed', 'items_parsed',
        'inserted_shipments', 'updated_shipments', 'inserted_items', 'error_count',
        'warnings', 'errors', 'created_by', 'started_at', 'completed_at',
    ];

    protected $casts = [
        'warnings' => 'array',
        'errors' => 'array',
        'started_at' => 'datetime',
        'completed_at' => 'datetime',
    ];

    public function store(): BelongsTo
    {
        return $this->belongsTo(Store::class);
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}
