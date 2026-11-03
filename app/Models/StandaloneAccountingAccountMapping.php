<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class StandaloneAccountingAccountMapping extends Model
{
    protected $table = 'standalone_accounting_account_mappings';

    protected $fillable = [
        'legacy_account_code',
        'standalone_account_code',
        'purpose',
        'is_active',
    ];

    protected $casts = [
        'is_active' => 'boolean',
    ];
}
