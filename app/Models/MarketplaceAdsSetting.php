<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class MarketplaceAdsSetting extends Model
{
    use HasFactory;

    protected $fillable = [
        'store_id',
        'target_roas',
        'admin_fee_mode',
        'admin_fee_pct',
        'notes',
    ];

    protected $casts = [
        'target_roas'   => 'float',
        'admin_fee_pct' => 'float',
    ];
}
