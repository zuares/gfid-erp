<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class StandaloneProductionAccountMapping extends Model
{
    protected $table = 'standalone_production_account_mappings';

    protected $fillable = [
        'source_type',
        'legacy_account_code',
        'line_side',
        'standalone_account_code',
        'stage',
        'purpose',
        'is_active',
    ];

    protected $casts = [
        'is_active' => 'boolean',
    ];
}
