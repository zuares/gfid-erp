<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class MarketplaceImportedAdSpend extends Model
{
    protected $table = 'marketplace_imported_ad_spends';

    protected $fillable = [
        'store_id',
        'channel',
        'date',
        'spend',
        'source_file',
        'import_batch_id',
        'raw_json',
    ];

    protected $casts = [
        'store_id' => 'integer',
        'date' => 'date',
        'spend' => 'decimal:2',
        'raw_json' => 'array',
    ];
}
